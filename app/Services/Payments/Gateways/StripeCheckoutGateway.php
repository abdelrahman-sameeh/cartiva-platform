<?php

namespace App\Services\Payments\Gateways;

use App\Models\Payment;
use App\Models\User;
use Stripe\Checkout\Session;
use Stripe\StripeClient;

class StripeCheckoutGateway
{
    public function __construct(private readonly StripeClient $stripe) {}

    public function createSession(
        Payment $payment,
        User $customer,
        string $successUrl,
        string $cancelUrl
    ): Session {
        $orderId = (string) $payment->order_id;
        $paymentId = (string) $payment->id;

        return $this->stripe->checkout->sessions->create([
            'mode' => 'payment',
            'client_reference_id' => $orderId,
            'customer_email' => $customer->email,
            'line_items' => [[
                'quantity' => 1,
                'price_data' => [
                    'currency' => $payment->currency,
                    'unit_amount' => $payment->amount_minor,
                    'product_data' => [
                        'name' => "Printore order #{$orderId}",
                    ],
                ],
            ]],
            'metadata' => [
                'order_id' => $orderId,
                'payment_id' => $paymentId,
            ],
            'payment_intent_data' => [
                'transfer_group' => "ORDER_{$orderId}",
                'metadata' => [
                    'order_id' => $orderId,
                    'payment_id' => $paymentId,
                ],
            ],
            'success_url' => $successUrl,
            'cancel_url' => $cancelUrl,
        ], [
            'idempotency_key' => $payment->idempotency_key,
        ]);
    }

    public function retrieveSession(string $checkoutSessionId): Session
    {
        return $this->stripe->checkout->sessions->retrieve($checkoutSessionId);
    }
}
