<?php

namespace App\Services\Payments\Gateways;

use App\Exceptions\StripeSettlementNotReadyException;
use App\Models\SellerTransfer;
use LogicException;
use Stripe\StripeClient;
use Stripe\Transfer;

class StripeTransferGateway
{
    public function __construct(private readonly StripeClient $stripe) {}

    /**
     * @return array{
     *     charge_id: string,
     *     payment_amount_minor: int,
     *     payment_currency: string,
     *     settlement_amount_minor: int,
     *     settlement_currency: string,
     *     stripe_fee_minor: int
     * }
     */
    public function sourceTransaction(string $paymentIntentId): array
    {
        $paymentIntent = $this->stripe->paymentIntents->retrieve($paymentIntentId, [
            'expand' => ['latest_charge.balance_transaction'],
        ]);
        $charge = $paymentIntent->latest_charge;
        $chargeId = is_string($charge) ? $charge : $charge?->id;
        $balanceTransaction = is_string($charge) ? null : $charge?->balance_transaction;

        if (! is_string($chargeId) || $chargeId === '') {
            throw new LogicException('The paid Stripe PaymentIntent has no source charge.');
        }

        if (is_string($balanceTransaction) || $balanceTransaction === null) {
            throw new StripeSettlementNotReadyException(
                'Stripe has not created the charge balance transaction yet.'
            );
        }

        return [
            'charge_id' => $chargeId,
            'payment_amount_minor' => (int) $paymentIntent->amount,
            'payment_currency' => strtolower((string) $paymentIntent->currency),
            'settlement_amount_minor' => (int) $balanceTransaction->amount,
            'settlement_currency' => strtolower((string) $balanceTransaction->currency),
            'stripe_fee_minor' => (int) $balanceTransaction->fee,
        ];
    }

    public function createTransfer(
        SellerTransfer $sellerTransfer,
        int $orderId,
        string $sourceChargeId
    ): Transfer {
        if ($sellerTransfer->transfer_amount_minor === null
            || $sellerTransfer->settlement_currency === null) {
            throw new LogicException('The seller transfer has no net transfer amount.');
        }

        return $this->stripe->transfers->create([
            'amount' => $sellerTransfer->transfer_amount_minor,
            'currency' => $sellerTransfer->settlement_currency,
            'destination' => $sellerTransfer->stripe_account_id,
            'source_transaction' => $sourceChargeId,
            'transfer_group' => "ORDER_{$orderId}",
            'metadata' => [
                'order_id' => (string) $orderId,
                'payment_id' => (string) $sellerTransfer->payment_id,
                'sub_order_id' => (string) $sellerTransfer->sub_order_id,
                'seller_id' => (string) $sellerTransfer->seller_id,
            ],
        ], [
            'idempotency_key' => $sellerTransfer->idempotency_key,
        ]);
    }
}
