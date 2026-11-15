<?php

namespace Tests\Feature\Services;

use App\Models\Address;
use App\Models\Coupon;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Refund;
use App\Models\User;
use App\Services\OrderService;
use App\Services\Payments\Gateways\StripeRefundGateway;
use App\Services\Payments\RefundService;
use App\Support\ProductVariantOptions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Mockery;
use Stripe\Refund as StripeRefund;
use Tests\TestCase;

class OrderStatusWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_cash_order_is_paid_after_all_seller_orders_are_completed(): void
    {
        [$buyer, $address] = $this->buyer();
        $this->addToCart($buyer, 'منتج البائع الأول', 100);
        $this->addToCart($buyer, 'منتج البائع الثاني', 200);
        $orders = app(OrderService::class);
        $order = $orders->create($buyer, [
            'phone' => '01012345678',
            'address_id' => $address->id,
            'payment_method' => 'cash',
        ]);

        foreach ($order->subOrders()->with('seller')->get() as $subOrder) {
            $orders->updateSubOrderStatus($subOrder->seller, $subOrder, 'completed');
        }

        $this->assertSame('completed', $order->refresh()->status);
        $this->assertSame('paid', $order->payment_status);
    }

    public function test_cancelling_before_shipping_restores_inventory_once(): void
    {
        [$buyer, $address] = $this->buyer();
        $variant = $this->addToCart($buyer, 'منتج ملغي', 100, 2);
        $orders = app(OrderService::class);
        $order = $orders->create($buyer, [
            'phone' => '01012345678',
            'address_id' => $address->id,
            'payment_method' => 'cash',
        ]);
        $subOrder = $order->subOrders()->with('seller')->firstOrFail();

        $this->assertSame(3, $variant->refresh()->stock);
        $orders->updateSubOrderStatus($subOrder->seller, $subOrder, 'cancelled');
        $this->assertSame(5, $variant->refresh()->stock);

        try {
            $orders->updateSubOrderStatus($subOrder->seller, $subOrder, 'cancelled');
            $this->fail('A terminal order must not be cancelled twice.');
        } catch (ValidationException) {
            $this->assertSame(5, $variant->refresh()->stock);
        }

        $this->assertSame('cancelled', $order->refresh()->status);
    }

    public function test_a_paid_card_sub_order_is_refunded_when_cancelled(): void
    {
        [$buyer, $address] = $this->buyer();
        $this->addToCart($buyer, 'منتج مدفوع ملغي', 125);
        $orders = app(OrderService::class);
        $order = $orders->create($buyer, [
            'phone' => '01012345678',
            'address_id' => $address->id,
            'payment_method' => 'card',
        ]);
        $payment = $order->payments()->create([
            'provider' => 'stripe',
            'provider_payment_id' => 'pi_refund_test',
            'checkout_session_id' => 'cs_refund_test',
            'idempotency_key' => (string) Str::uuid(),
            'amount_minor' => 12500,
            'currency' => 'egp',
            'status' => Payment::STATUS_PAID,
            'paid_at' => now(),
        ]);
        $order->update(['payment_status' => 'paid']);
        $subOrder = $order->subOrders()->with('seller')->firstOrFail();

        $orders->updateSubOrderStatus($subOrder->seller, $subOrder, 'cancelled');

        $refund = Refund::query()->sole();
        $this->assertSame($payment->id, $refund->payment_id);
        $this->assertSame(12500, $refund->amount_minor);
        $this->assertSame('0.00', $order->refresh()->total_price);

        $gateway = Mockery::mock(StripeRefundGateway::class);
        $gateway->shouldReceive('createRefund')
            ->once()
            ->andReturn(StripeRefund::constructFrom(['id' => 're_test_cancel']));
        $this->app->instance(StripeRefundGateway::class, $gateway);

        $this->assertSame(1, app(RefundService::class)->processOutstanding());
        $this->assertSame(Refund::STATUS_SUCCEEDED, $refund->refresh()->status);
        $this->assertSame('refunded', $order->refresh()->payment_status);
    }

    public function test_cancelling_releases_coupon_and_recalculates_order_totals(): void
    {
        [$buyer, $address] = $this->buyer();
        $variant = $this->addToCart($buyer, 'منتج عليه كوبون', 100);
        $coupon = Coupon::create([
            'code' => 'CANCEL10',
            'percentage' => 10,
            'expire_date' => now()->addMonth()->toDateString(),
            'max_usage' => 5,
            'used_count' => 0,
            'seller_id' => $variant->product->seller_id,
            'is_active' => true,
        ]);
        $buyer->cart->coupons()->create(['coupon_id' => $coupon->id]);
        $orders = app(OrderService::class);
        $order = $orders->create($buyer, [
            'phone' => '01012345678',
            'address_id' => $address->id,
            'payment_method' => 'cash',
        ]);
        $subOrder = $order->subOrders()->with('seller')->firstOrFail();

        $orders->updateSubOrderStatus($subOrder->seller, $subOrder, 'cancelled');

        $this->assertSame(0, $coupon->refresh()->used_count);
        $this->assertSame('0.00', $order->refresh()->total_price);
        $this->assertSame('cancelled', $order->status);
    }

    private function buyer(): array
    {
        $buyer = User::factory()->create();
        $address = Address::create([
            'user_id' => $buyer->id,
            'country' => 'EG',
            'city' => 'القاهرة',
            'street' => 'شارع التحرير',
            'is_default' => true,
        ]);
        $buyer->cart()->create();

        return [$buyer, $address];
    }

    private function addToCart(User $buyer, string $title, float $price, int $quantity = 1)
    {
        $seller = User::factory()->create();
        $product = Product::create([
            'title' => $title,
            'description' => 'منتج لاختبار دورة حالات الطلب.',
            'price' => $price,
            'discount_amount' => 0,
            'seller_id' => $seller->id,
            'is_active' => true,
        ]);
        $options = ['color' => 'أسود'];
        $variant = $product->variants()->create([
            'stock' => 5,
            'option_signature' => ProductVariantOptions::signature($options),
        ]);
        $variant->options()->create(['key' => 'color', 'value' => 'أسود']);
        $buyer->cart->items()->create([
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
            'quantity' => $quantity,
        ]);

        return $variant;
    }
}
