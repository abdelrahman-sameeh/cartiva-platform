<?php

namespace Tests\Feature\Webhooks;

use App\Models\Order\Order;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StripeCheckoutWebhookTest extends TestCase
{
    use RefreshDatabase;

    private const WEBHOOK_SECRET = 'whsec_checkout_test';

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.stripe.checkout.webhook_secret' => self::WEBHOOK_SECRET]);
    }

    public function test_a_signed_paid_session_marks_the_order_paid_once(): void
    {
        [$order, $payment] = $this->payment();

        $this->postSignedEvent('evt_paid', 'checkout.session.completed', $order, $payment)
            ->assertNoContent();
        $this->postSignedEvent('evt_paid', 'checkout.session.completed', $order, $payment)
            ->assertNoContent();

        $this->assertDatabaseHas('orders', ['id' => $order->id, 'payment_status' => 'paid']);
        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'provider_payment_id' => 'pi_test_order',
            'status' => Payment::STATUS_PAID,
        ]);
        $this->assertDatabaseCount('stripe_webhook_events', 1);
    }

    public function test_a_mismatched_amount_never_marks_the_order_paid(): void
    {
        [$order, $payment] = $this->payment();

        $this->postSignedEvent(
            'evt_wrong_amount',
            'checkout.session.completed',
            $order,
            $payment,
            ['amount_total' => 1]
        )->assertNoContent();

        $this->assertDatabaseHas('orders', ['id' => $order->id, 'payment_status' => 'pending']);
        $this->assertDatabaseHas('payments', ['id' => $payment->id, 'status' => Payment::STATUS_PENDING]);
    }

    public function test_a_completed_but_unpaid_session_does_not_mark_the_order_paid(): void
    {
        [$order, $payment] = $this->payment();

        $this->postSignedEvent(
            'evt_unpaid',
            'checkout.session.completed',
            $order,
            $payment,
            ['payment_status' => 'unpaid']
        )->assertNoContent();

        $this->assertDatabaseHas('orders', ['id' => $order->id, 'payment_status' => 'pending']);
        $this->assertDatabaseHas('payments', ['id' => $payment->id, 'status' => Payment::STATUS_PENDING]);
    }

    public function test_a_failed_event_does_not_downgrade_a_paid_order(): void
    {
        [$order, $payment] = $this->payment();
        $this->postSignedEvent('evt_paid_first', 'checkout.session.completed', $order, $payment)
            ->assertNoContent();
        $this->postSignedEvent(
            'evt_late_failure',
            'checkout.session.async_payment_failed',
            $order,
            $payment,
            ['payment_status' => 'unpaid']
        )->assertNoContent();

        $this->assertDatabaseHas('orders', ['id' => $order->id, 'payment_status' => 'paid']);
        $this->assertDatabaseHas('payments', ['id' => $payment->id, 'status' => Payment::STATUS_PAID]);
    }

    public function test_an_expired_session_allows_a_new_attempt_without_marking_the_order_paid(): void
    {
        [$order, $payment] = $this->payment();

        $this->postSignedEvent(
            'evt_expired',
            'checkout.session.expired',
            $order,
            $payment,
            ['payment_status' => 'unpaid', 'status' => 'expired']
        )->assertNoContent();

        $this->assertDatabaseHas('orders', ['id' => $order->id, 'payment_status' => 'pending']);
        $this->assertDatabaseHas('payments', ['id' => $payment->id, 'status' => Payment::STATUS_EXPIRED]);
    }

    public function test_an_invalid_signature_is_rejected(): void
    {
        $this->call('POST', route('webhooks.stripe.checkout'), [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_STRIPE_SIGNATURE' => 'invalid',
        ], '{}')->assertStatus(400);
    }

    private function payment(): array
    {
        $buyer = User::factory()->create();
        $order = Order::create([
            'user_id' => $buyer->id,
            'subtotal' => 180,
            'discount' => 0,
            'total_price' => 180,
            'phone' => '01012345678',
            'status' => 'pending',
            'payment_status' => 'pending',
            'payment_method' => 'card',
        ]);
        $payment = $order->payments()->create([
            'provider' => 'stripe',
            'checkout_session_id' => 'cs_test_order',
            'idempotency_key' => '4dc6f97e-2ea4-4fe1-a585-faba8c32b15b',
            'amount_minor' => 18000,
            'currency' => 'egp',
            'status' => Payment::STATUS_PENDING,
        ]);

        return [$order, $payment];
    }

    private function postSignedEvent(
        string $eventId,
        string $type,
        Order $order,
        Payment $payment,
        array $overrides = []
    ) {
        $session = array_merge([
            'id' => $payment->checkout_session_id,
            'object' => 'checkout.session',
            'mode' => 'payment',
            'status' => 'complete',
            'payment_status' => 'paid',
            'payment_intent' => 'pi_test_order',
            'amount_total' => $payment->amount_minor,
            'currency' => $payment->currency,
            'client_reference_id' => (string) $order->id,
            'metadata' => [
                'order_id' => (string) $order->id,
                'payment_id' => (string) $payment->id,
            ],
        ], $overrides);
        $payload = json_encode([
            'id' => $eventId,
            'object' => 'event',
            'type' => $type,
            'data' => ['object' => $session],
        ], JSON_THROW_ON_ERROR);
        $timestamp = time();
        $signature = hash_hmac('sha256', "{$timestamp}.{$payload}", self::WEBHOOK_SECRET);

        return $this->call('POST', route('webhooks.stripe.checkout'), [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_STRIPE_SIGNATURE' => "t={$timestamp},v1={$signature}",
        ], $payload);
    }
}
