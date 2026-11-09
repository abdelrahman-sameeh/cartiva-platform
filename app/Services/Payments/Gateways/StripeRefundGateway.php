<?php

namespace App\Services\Payments\Gateways;

use App\Models\Refund;
use Stripe\Refund as StripeRefund;
use Stripe\StripeClient;

class StripeRefundGateway
{
    public function __construct(private readonly StripeClient $stripe) {}

    public function createRefund(Refund $refund): StripeRefund
    {
        return $this->stripe->refunds->create([
            'payment_intent' => $refund->payment->provider_payment_id,
            'amount' => $refund->amount_minor,
            'metadata' => [
                'order_id' => (string) $refund->order_id,
                'sub_order_id' => (string) $refund->sub_order_id,
                'refund_id' => (string) $refund->id,
            ],
        ], ['idempotency_key' => $refund->idempotency_key]);
    }
}
