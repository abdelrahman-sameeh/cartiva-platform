<?php

namespace App\Services\Payments;

use App\Exceptions\StripeSettlementNotReadyException;
use App\Models\Order\Order;
use App\Models\Payment;
use App\Models\SellerTransfer;
use App\Services\Payments\Gateways\StripeTransferGateway;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use LogicException;
use Stripe\Exception\ApiConnectionException;
use Stripe\Exception\ApiErrorException;
use Stripe\Exception\RateLimitException;
use Throwable;

class SellerTransferService
{
    public function __construct(private readonly StripeTransferGateway $stripe) {}

    public function processPaidOrder(int $orderId): void
    {
        $payment = $this->prepareTransfers($orderId);
        $transfers = SellerTransfer::query()
            ->where('payment_id', $payment->id)
            ->whereHas('subOrder', fn ($query) => $query->where('status', 'completed'))
            ->whereIn('status', [
                SellerTransfer::STATUS_PENDING,
                SellerTransfer::STATUS_PROCESSING,
                SellerTransfer::STATUS_FAILED,
            ])
            ->get();

        if ($transfers->isEmpty()) {
            return;
        }

        if (! is_string($payment->provider_payment_id) || $payment->provider_payment_id === '') {
            throw new LogicException('A paid Stripe payment must have a PaymentIntent ID before transfers.');
        }

        try {
            $sourceTransaction = $this->stripe->sourceTransaction($payment->provider_payment_id);
        } catch (StripeSettlementNotReadyException) {
            Log::info('Stripe seller transfer is waiting for settlement data.', [
                'order_id' => $orderId,
                'payment_id' => $payment->id,
            ]);

            return;
        }
        $this->validateSourceTransaction($payment, $sourceTransaction);
        $this->allocateTransferAmounts(
            $payment,
            $sourceTransaction['settlement_amount_minor'],
            $sourceTransaction['settlement_currency'],
            $sourceTransaction['stripe_fee_minor'],
        );
        $retryableFailure = null;

        foreach ($transfers as $transfer) {
            try {
                $this->processTransfer($transfer->id, $orderId, $sourceTransaction['charge_id']);
            } catch (ApiErrorException $exception) {
                $this->markFailed($transfer->id, $exception);

                if ($this->isRetryable($exception)) {
                    $retryableFailure = $exception;
                }
            }
        }

        if ($retryableFailure) {
            throw $retryableFailure;
        }
    }

    public function retryOutstanding(?int $orderId = null, ?int $sellerId = null): int
    {
        $orderIds = SellerTransfer::query()
            ->join('sub_orders', 'sub_orders.id', '=', 'seller_transfers.sub_order_id')
            ->whereIn('seller_transfers.status', [
                SellerTransfer::STATUS_PENDING,
                SellerTransfer::STATUS_PROCESSING,
                SellerTransfer::STATUS_FAILED,
                SellerTransfer::STATUS_BLOCKED,
            ])
            ->when($orderId, fn ($query) => $query->where('sub_orders.order_id', $orderId))
            ->when($sellerId, fn ($query) => $query->where('seller_transfers.seller_id', $sellerId))
            ->distinct()
            ->pluck('sub_orders.order_id');

        foreach ($orderIds as $outstandingOrderId) {
            $this->processPaidOrder((int) $outstandingOrderId);
        }

        return $orderIds->count();
    }

    private function prepareTransfers(int $orderId): Payment
    {
        return DB::transaction(function () use ($orderId): Payment {
            $order = Order::query()->lockForUpdate()->findOrFail($orderId);

            if ($order->payment_status !== 'paid') {
                throw new LogicException('Seller transfers can only be prepared for a paid order.');
            }

            $payment = $order->payments()
                ->where('provider', 'stripe')
                ->where('status', Payment::STATUS_PAID)
                ->lockForUpdate()
                ->latest('id')
                ->firstOrFail();
            $subOrders = $order->subOrders()
                ->with('seller.stripeAccount')
                ->lockForUpdate()
                ->get();
            $amounts = $subOrders->mapWithKeys(
                fn ($subOrder): array => [$subOrder->id => $this->minorUnits($subOrder->total_price)]
            );

            if ($amounts->sum() !== $payment->amount_minor) {
                throw new LogicException('Sub-order totals do not match the paid Stripe amount.');
            }

            foreach ($subOrders as $subOrder) {
                $stripeAccount = $subOrder->seller->stripeAccount;
                $isReady = $stripeAccount?->isReady() === true;
                $transfer = SellerTransfer::query()->firstOrNew([
                    'sub_order_id' => $subOrder->id,
                ]);

                if (! $transfer->exists) {
                    $transfer->fill([
                        'payment_id' => $payment->id,
                        'seller_id' => $subOrder->seller_id,
                        'stripe_account_id' => $stripeAccount?->stripe_account_id,
                        'idempotency_key' => (string) Str::uuid(),
                        'amount_minor' => $amounts[$subOrder->id],
                        'currency' => $payment->currency,
                        'status' => $amounts[$subOrder->id] === 0
                            ? SellerTransfer::STATUS_SKIPPED
                            : ($isReady ? SellerTransfer::STATUS_PENDING : SellerTransfer::STATUS_BLOCKED),
                        'failure_reason' => match (true) {
                            $amounts[$subOrder->id] === 0 => 'The sub-order has no seller amount to transfer.',
                            ! $isReady => 'Seller Stripe account is not ready for transfers.',
                            default => null,
                        },
                    ])->save();

                    continue;
                }

                if ($transfer->status === SellerTransfer::STATUS_BLOCKED && $isReady) {
                    $transfer->update([
                        'stripe_account_id' => $stripeAccount->stripe_account_id,
                        'status' => SellerTransfer::STATUS_PENDING,
                        'failure_reason' => null,
                    ]);
                }
            }

            return $payment;
        });
    }

    private function processTransfer(int $transferId, int $orderId, string $sourceChargeId): void
    {
        $transfer = DB::transaction(function () use ($transferId): ?SellerTransfer {
            $transfer = SellerTransfer::query()->lockForUpdate()->findOrFail($transferId);

            if (in_array($transfer->status, [
                SellerTransfer::STATUS_SUCCEEDED,
                SellerTransfer::STATUS_SKIPPED,
            ], true)) {
                return null;
            }

            $stripeAccount = $transfer->seller->stripeAccount;

            if (! $stripeAccount?->isReady()) {
                $transfer->update([
                    'status' => SellerTransfer::STATUS_BLOCKED,
                    'failure_reason' => 'Seller Stripe account is not ready for transfers.',
                ]);

                return null;
            }

            if ($transfer->transfer_amount_minor === null || $transfer->settlement_currency === null) {
                throw new LogicException('The seller transfer has no net transfer amount.');
            }

            $transfer->update([
                'stripe_account_id' => $stripeAccount->stripe_account_id,
                'status' => SellerTransfer::STATUS_PROCESSING,
                'failure_reason' => null,
                'attempted_at' => now(),
            ]);

            return $transfer->refresh();
        });

        if (! $transfer) {
            return;
        }

        $stripeTransfer = $this->stripe->createTransfer($transfer, $orderId, $sourceChargeId);

        DB::transaction(function () use ($transfer, $stripeTransfer): void {
            $lockedTransfer = SellerTransfer::query()->lockForUpdate()->findOrFail($transfer->id);
            $lockedTransfer->update([
                'stripe_transfer_id' => $stripeTransfer->id,
                'status' => SellerTransfer::STATUS_SUCCEEDED,
                'failure_reason' => null,
                'transferred_at' => now(),
            ]);
        });
    }

    private function markFailed(int $transferId, Throwable $exception): void
    {
        SellerTransfer::query()
            ->whereKey($transferId)
            ->where('status', '!=', SellerTransfer::STATUS_SUCCEEDED)
            ->update([
                'status' => SellerTransfer::STATUS_FAILED,
                'failure_reason' => Str::limit($exception->getMessage(), 1000, ''),
            ]);

        Log::error('Stripe seller transfer failed.', [
            'seller_transfer_id' => $transferId,
            'exception' => $exception,
        ]);
    }

    private function isRetryable(ApiErrorException $exception): bool
    {
        return $exception instanceof ApiConnectionException
            || $exception instanceof RateLimitException
            || ($exception->getHttpStatus() !== null && $exception->getHttpStatus() >= 500);
    }

    /**
     * @param  array{
     *     charge_id: string,
     *     payment_amount_minor: int,
     *     payment_currency: string,
     *     settlement_amount_minor: int,
     *     settlement_currency: string,
     *     stripe_fee_minor: int
     * }  $sourceTransaction
     */
    private function validateSourceTransaction(Payment $payment, array $sourceTransaction): void
    {
        if ($sourceTransaction['payment_amount_minor'] !== $payment->amount_minor
            || $sourceTransaction['payment_currency'] !== strtolower($payment->currency)) {
            throw new LogicException('Stripe PaymentIntent amount does not match the local payment.');
        }

        if ($sourceTransaction['settlement_amount_minor'] <= 0
            || $sourceTransaction['settlement_currency'] === ''
            || $sourceTransaction['stripe_fee_minor'] < 0
            || $sourceTransaction['stripe_fee_minor'] > $sourceTransaction['settlement_amount_minor']) {
            throw new LogicException('Stripe returned an invalid settlement amount.');
        }
    }

    private function allocateTransferAmounts(
        Payment $payment,
        int $settlementAmount,
        string $settlementCurrency,
        int $stripeFee
    ): void {
        DB::transaction(function () use ($payment, $settlementAmount, $settlementCurrency, $stripeFee): void {
            $transfers = SellerTransfer::query()
                ->where('payment_id', $payment->id)
                ->where('amount_minor', '>', 0)
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            if ($transfers->sum('amount_minor') !== $payment->amount_minor) {
                throw new LogicException('Seller transfer amounts do not match the paid Stripe amount.');
            }

            $platformFeeBps = $this->platformFeeBps($transfers);
            $platformFee = $this->percentageOf($settlementAmount, $platformFeeBps);
            $settlementAllocations = $this->allocateProportionally($transfers, $settlementAmount, $payment->amount_minor);
            $stripeFeeAllocations = $this->allocateProportionally($transfers, $stripeFee, $payment->amount_minor);
            $platformFeeAllocations = $this->allocateProportionally($transfers, $platformFee, $payment->amount_minor);

            foreach ($transfers as $transfer) {
                $netTransferAmount = max(
                    0,
                    $settlementAllocations[$transfer->id]
                        - $stripeFeeAllocations[$transfer->id]
                        - $platformFeeAllocations[$transfer->id]
                );
                $values = [
                    'settlement_amount_minor' => $settlementAllocations[$transfer->id],
                    'settlement_currency' => $settlementCurrency,
                    'stripe_fee_minor' => $stripeFeeAllocations[$transfer->id],
                    'platform_fee_minor' => $platformFeeAllocations[$transfer->id],
                    'platform_fee_bps' => $platformFeeBps,
                    'transfer_amount_minor' => $netTransferAmount,
                ];

                if ($netTransferAmount === 0
                    && $transfer->status !== SellerTransfer::STATUS_SUCCEEDED) {
                    $values += [
                        'status' => SellerTransfer::STATUS_SKIPPED,
                        'failure_reason' => 'Seller proceeds are zero after Stripe and platform fees.',
                    ];
                }

                $transfer->update($values);
            }
        });
    }

    private function allocateProportionally(Collection $transfers, int $total, int $weightTotal): array
    {
        $allocations = [];
        $remainders = [];
        $allocated = 0;

        foreach ($transfers as $transfer) {
            $weightedAmount = $total * $transfer->amount_minor;
            $allocations[$transfer->id] = intdiv($weightedAmount, $weightTotal);
            $remainders[$transfer->id] = $weightedAmount % $weightTotal;
            $allocated += $allocations[$transfer->id];
        }

        $rankedTransferIds = $transfers->pluck('id')->sortByDesc(
            fn (int $transferId): int => $remainders[$transferId]
        )->values();

        for ($index = 0; $allocated < $total; $index++, $allocated++) {
            $allocations[$rankedTransferIds[$index]]++;
        }

        return $allocations;
    }

    private function percentageOf(int $amount, int $basisPoints): int
    {
        return intdiv(($amount * $basisPoints) + 5000, 10000);
    }

    private function platformFeeBps(Collection $transfers): int
    {
        $snapshots = $transfers->pluck('platform_fee_bps')
            ->filter(fn ($basisPoints): bool => $basisPoints !== null)
            ->unique()
            ->values();

        if ($snapshots->count() > 1) {
            throw new LogicException('Seller transfers for one payment have inconsistent platform commissions.');
        }

        if ($snapshots->isNotEmpty()) {
            return (int) $snapshots->first();
        }

        $basisPoints = (int) config('services.stripe.fees.platform_commission_bps', 500);

        if ($basisPoints < 0 || $basisPoints > 10000) {
            throw new LogicException('Platform commission must be between 0 and 100 percent.');
        }

        return $basisPoints;
    }

    private function minorUnits(string $amount): int
    {
        if (! preg_match('/^(\d+)(?:\.(\d{1,2}))?$/', $amount, $matches)) {
            throw new LogicException('A seller amount must have at most two decimal places.');
        }

        return ((int) $matches[1] * 100) + (int) str_pad($matches[2] ?? '', 2, '0');
    }
}
