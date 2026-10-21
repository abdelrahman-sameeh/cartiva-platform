<?php

namespace Tests\Feature\Services;

use App\Models\Order\Order;
use App\Models\Payment;
use App\Models\User;
use App\Services\Payments\Gateways\StripeCheckoutGateway;
use App\Services\Payments\OrderCheckoutService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Mockery;
use Stripe\Checkout\Session;
use Stripe\Exception\InvalidRequestException;
use Tests\TestCase;

class OrderCheckoutServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_one_checkout_attempt_using_minor_units(): void
    {
        [$buyer, $order] = $this->order('123.45');
        $gateway = Mockery::mock(StripeCheckoutGateway::class);
        $gateway->shouldReceive('createSession')
            ->once()
            ->withArgs(function (Payment $payment, User $customer): bool {
                return $payment->amount_minor === 12345
                    && $payment->currency === 'egp'
                    && $customer->email === 'buyer-checkout@example.test';
            })
            ->andReturn($this->checkoutSession());
        $this->app->instance(StripeCheckoutGateway::class, $gateway);

        $url = app(OrderCheckoutService::class)->checkoutUrl(
            $buyer,
            $order,
            'https://example.test/success',
            'https://example.test/cancel'
        );

        $this->assertSame('https://checkout.stripe.test/order', $url);
        $this->assertDatabaseCount('payments', 1);
        $this->assertDatabaseHas('payments', [
            'order_id' => $order->id,
            'checkout_session_id' => 'cs_test_order',
            'amount_minor' => 12345,
            'currency' => 'egp',
            'status' => Payment::STATUS_PENDING,
        ]);
    }

    public function test_repeated_clicks_reuse_the_open_checkout_session(): void
    {
        [$buyer, $order] = $this->order();
        $gateway = Mockery::mock(StripeCheckoutGateway::class);
        $gateway->shouldReceive('createSession')->once()->andReturn($this->checkoutSession());
        $gateway->shouldReceive('retrieveSession')->twice()->andReturn($this->checkoutSession());
        $this->app->instance(StripeCheckoutGateway::class, $gateway);
        $service = app(OrderCheckoutService::class);

        $service->checkoutUrl($buyer, $order, 'https://example.test/success', 'https://example.test/cancel');
        $service->checkoutUrl($buyer, $order, 'https://example.test/success', 'https://example.test/cancel');
        $service->checkoutUrl($buyer, $order, 'https://example.test/success', 'https://example.test/cancel');

        $this->assertDatabaseCount('payments', 1);
    }

    public function test_an_expired_session_is_replaced_by_one_new_attempt(): void
    {
        [$buyer, $order] = $this->order();
        $order->payments()->create([
            'provider' => 'stripe',
            'checkout_session_id' => 'cs_test_expired',
            'idempotency_key' => '5e9f4964-dc54-4d65-88e9-aa8c8c34bd83',
            'amount_minor' => 18000,
            'currency' => 'egp',
            'status' => Payment::STATUS_PENDING,
        ]);
        $expiredSession = Session::constructFrom([
            'id' => 'cs_test_expired',
            'object' => 'checkout.session',
            'status' => 'expired',
            'payment_status' => 'unpaid',
        ]);
        $gateway = Mockery::mock(StripeCheckoutGateway::class);
        $gateway->shouldReceive('retrieveSession')->once()->andReturn($expiredSession);
        $gateway->shouldReceive('createSession')->once()->andReturn($this->checkoutSession());
        $this->app->instance(StripeCheckoutGateway::class, $gateway);

        app(OrderCheckoutService::class)->checkoutUrl(
            $buyer,
            $order,
            'https://example.test/success',
            'https://example.test/cancel'
        );

        $this->assertDatabaseCount('payments', 2);
        $this->assertDatabaseHas('payments', [
            'checkout_session_id' => 'cs_test_expired',
            'status' => Payment::STATUS_EXPIRED,
        ]);
        $this->assertDatabaseHas('payments', [
            'checkout_session_id' => 'cs_test_order',
            'status' => Payment::STATUS_PENDING,
        ]);
    }

    public function test_a_permanent_stripe_error_allows_a_fresh_attempt(): void
    {
        [$buyer, $order] = $this->order();
        $gateway = Mockery::mock(StripeCheckoutGateway::class);
        $gateway->shouldReceive('createSession')
            ->once()
            ->andThrow(InvalidRequestException::factory('Invalid Checkout request.'));
        $gateway->shouldReceive('createSession')->once()->andReturn($this->checkoutSession());
        $this->app->instance(StripeCheckoutGateway::class, $gateway);
        $service = app(OrderCheckoutService::class);

        try {
            $service->checkoutUrl(
                $buyer,
                $order,
                'https://example.test/success',
                'https://example.test/cancel'
            );
        } catch (InvalidRequestException) {
            // The controller converts this provider error into a safe user message.
        }

        $service->checkoutUrl(
            $buyer,
            $order,
            'https://example.test/success',
            'https://example.test/cancel'
        );

        $this->assertDatabaseCount('payments', 2);
        $this->assertSame(
            [Payment::STATUS_FAILED, Payment::STATUS_PENDING],
            $order->payments()->orderBy('id')->pluck('status')->all()
        );
    }

    public function test_a_customer_cannot_pay_another_customers_order(): void
    {
        [, $order] = $this->order();
        $otherBuyer = User::factory()->create();

        $this->expectExceptionMessage('هذا الطلب لا يخص حسابك.');

        app(OrderCheckoutService::class)->checkoutUrl(
            $otherBuyer,
            $order,
            'https://example.test/success',
            'https://example.test/cancel'
        );
    }

    public function test_a_paid_order_cannot_start_another_checkout(): void
    {
        [$buyer, $order] = $this->order();
        $order->update(['payment_status' => 'paid']);

        $this->expectException(ValidationException::class);

        app(OrderCheckoutService::class)->checkoutUrl(
            $buyer,
            $order,
            'https://example.test/success',
            'https://example.test/cancel'
        );
    }

    private function order(string $total = '180.00'): array
    {
        $buyer = User::factory()->create(['email' => 'buyer-checkout@example.test']);
        $order = Order::create([
            'user_id' => $buyer->id,
            'subtotal' => $total,
            'discount' => 0,
            'total_price' => $total,
            'phone' => '01012345678',
            'status' => 'pending',
            'payment_status' => 'pending',
            'payment_method' => 'card',
        ]);

        return [$buyer, $order];
    }

    private function checkoutSession(): Session
    {
        return Session::constructFrom([
            'id' => 'cs_test_order',
            'object' => 'checkout.session',
            'mode' => 'payment',
            'status' => 'open',
            'payment_status' => 'unpaid',
            'url' => 'https://checkout.stripe.test/order',
        ]);
    }
}
