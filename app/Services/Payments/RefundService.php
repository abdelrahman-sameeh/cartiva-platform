<?php

namespace App\Services\Payments;

use App\Models\Order\SubOrder;
use App\Models\Payment;
use App\Models\Refund;
use App\Models\SellerTransfer;
use App\Services\Payments\Gateways\StripeRefundGateway;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class RefundService
{
    public function requestForCancelledSubOrder(SubOrder $subOrder): ?Refund
    {
        $subOrder->loadMissing('order');

        if ($subOrder->order->payment_status !== 'paid'
            || ! in_array($subOrder->order->payment_method, ['card', 'wallet'], true)) {
            return null;
        }

        if ($subOrder->sellerTransfer()->where('status', SellerTransfer::STATUS_SUCCEEDED)->exists()) {
            throw ValidationException::withMessages([
                'status' => 'لا يمكن إلغاء الطلب بعد تحويل مستحق البائع دون عملية عكس تحويل.',
            ]);
        }

        $payment = $subOrder->order->payments()
            ->where('status', Payment::STATUS_PAID)
            ->latest('id')
            ->firstOrFail();

        return Refund::query()->firstOrCreate(
            ['sub_order_id' => $subOrder->id],
            [
                'order_id' => $subOrder->order_id,
                'payment_id' => $payment->id,
                'idempotency_key' => (string) Str::uuid(),
                'amount_minor' => (int) round((float) $subOrder->total_price * 100),
                'currency' => $payment->currency,
                'status' => Refund::STATUS_PENDING,
            ]
        );
    }

    public function processOutstanding(): int
    {
        $refundIds = Refund::query()
            ->whereIn('status', [Refund::STATUS_PENDING, Refund::STATUS_FAILED])
            ->pluck('id');
        $processed = 0;

        foreach ($refundIds as $refundId) {
            try {
                $this->process((int) $refundId);
                $processed++;
            } catch (Throwable $exception) {
                Log::error('Stripe partial refund failed.', [
                    'refund_id' => $refundId,
                    'exception' => $exception,
                ]);
            }
        }

        return $processed;
    }

    private function process(int $refundId): void
    {
        $refund = DB::transaction(function () use ($refundId): ?Refund {
            $refund = Refund::query()->with('payment')->lockForUpdate()->findOrFail($refundId);

            if ($refund->status === Refund::STATUS_SUCCEEDED) {
                return null;
            }

            $refund->update(['status' => Refund::STATUS_PROCESSING, 'failure_reason' => null]);

            return $refund->refresh()->load('payment');
        });

        if (! $refund) {
            return;
        }

        try {
            $providerRefund = app(StripeRefundGateway::class)->createRefund($refund);
        } catch (Throwable $exception) {
            $refund->update([
                'status' => Refund::STATUS_FAILED,
                'failure_reason' => Str::limit($exception->getMessage(), 1000, ''),
            ]);

            throw $exception;
        }

        DB::transaction(function () use ($refund, $providerRefund): void {
            $lockedRefund = Refund::query()->lockForUpdate()->findOrFail($refund->id);
            $lockedRefund->update([
                'provider_refund_id' => $providerRefund->id,
                'status' => Refund::STATUS_SUCCEEDED,
                'failure_reason' => null,
                'refunded_at' => now(),
            ]);
            $refundedAmount = Refund::query()
                ->where('payment_id', $refund->payment_id)
                ->where('status', Refund::STATUS_SUCCEEDED)
                ->sum('amount_minor');

            if ($refundedAmount >= $refund->payment->amount_minor) {
                $refund->order()->update(['payment_status' => 'refunded']);
            }
        });
    }
}
