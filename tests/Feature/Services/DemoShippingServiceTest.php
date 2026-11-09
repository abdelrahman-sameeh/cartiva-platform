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
use App\Services\Shipping\DemoShippingService;
use App\Support\ProductVariantOptions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Mockery;
use Stripe\Refund as StripeRefund;
use Tests\TestCase;

class DemoShippingServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_cod_is_collected_and_settled_per_seller(): void
    {
        config([
            'services.demo_shipping.platform_commission_bps' => 500,
            'services.demo_shipping.cod_collection_bps' => 100,
            'services.demo_shipping.shipping_fee' => 10,
        ]);
        [$buyer, $address] = $this->buyer();
        $firstVariant = $this->addToCart($buyer, 'منتج البائع الأول', 100);
        $secondVariant = $this->addToCart($buyer, 'منتج البائع الثاني', 200);

        $order = app(OrderService::class)->create($buyer, [
            'phone' => '01012345678',
            'address_id' => $address->id,
            'payment_method' => 'cash',
        ]);
        $shipments = $order->subOrders()->with('shipment')->get()->pluck('shipment');

        $this->assertCount(2, $shipments);
        $this->assertSame(['100.00', '200.00'], $shipments->pluck('cod_amount')->sort()->values()->all());
        $this->assertSame(['84.00', '178.00'], $shipments->pluck('seller_net')->sort()->values()->all());
        $this->assertSame('pending', $order->payment_status);

        $shipping = app(DemoShippingService::class);
        $first = $shipments->first();
        $second = $shipments->last();
        $shipping->handleEvent($first->tracking_number, 'delivered');

        $this->assertSame('pending', $order->refresh()->payment_status);
        $this->assertSame('completed', $first->subOrder->refresh()->status);

        $shipping->handleEvent($first->tracking_number, 'remitted');
        $shipping->handleEvent($first->tracking_number, 'seller-paid');
        $shipping->handleEvent($second->tracking_number, 'delivered');

        $this->assertSame('paid', $order->refresh()->payment_status);
        $this->assertSame('completed', $order->status);
        $this->assertNotNull($first->refresh()->seller_paid_at);
        $this->assertSame(4, $firstVariant->refresh()->stock);
        $this->assertSame(4, $secondVariant->refresh()->stock);
    }

    public function test_a_returned_shipment_restores_inventory_once(): void
    {
        [$buyer, $address] = $this->buyer();
        $variant = $this->addToCart($buyer, 'منتج مرتجع', 100, 2);
        $order = app(OrderService::class)->create($buyer, [
            'phone' => '01012345678',
            'address_id' => $address->id,
            'payment_method' => 'cash',
        ]);
        $shipment = $order->subOrders()->firstOrFail()->shipment;
        $shipping = app(DemoShippingService::class);

        $this->assertSame(3, $variant->refresh()->stock);

        $shipping->handleEvent($shipment->tracking_number, 'in-transit');
        $shipping->handleEvent($shipment->tracking_number, 'returned');
        $shipping->handleEvent($shipment->tracking_number, 'returned');

        $this->assertSame(5, $variant->refresh()->stock);
        $this->assertSame('cancelled', $order->refresh()->status);
        $this->assertSame('pending', $order->payment_status);
    }

    public function test_a_paid_card_sub_order_is_partially_refunded_when_returned(): void
    {
        [$buyer, $address] = $this->buyer();
        $this->addToCart($buyer, 'منتج مدفوع مرتجع', 125);
        $order = app(OrderService::class)->create($buyer, [
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
        $shipment = $order->subOrders()->firstOrFail()->shipment;
        $shipping = app(DemoShippingService::class);

        $shipping->handleEvent($shipment->tracking_number, 'in-transit');
        $shipping->handleEvent($shipment->tracking_number, 'returned');

        $refund = Refund::query()->sole();
        $this->assertSame($payment->id, $refund->payment_id);
        $this->assertSame(12500, $refund->amount_minor);
        $this->assertSame('0.00', $order->refresh()->total_price);

        $gateway = Mockery::mock(StripeRefundGateway::class);
        $gateway->shouldReceive('createRefund')
            ->once()
            ->andReturn(StripeRefund::constructFrom(['id' => 're_test_return']));
        $this->app->instance(StripeRefundGateway::class, $gateway);

        $this->assertSame(1, app(RefundService::class)->processOutstanding());
        $this->assertSame(Refund::STATUS_SUCCEEDED, $refund->refresh()->status);
        $this->assertSame('refunded', $order->refresh()->payment_status);
    }

    public function test_cancelling_before_pickup_releases_coupon_and_recalculates_order_totals(): void
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
        $order = app(OrderService::class)->create($buyer, [
            'phone' => '01012345678',
            'address_id' => $address->id,
            'payment_method' => 'cash',
        ]);
        $subOrder = $order->subOrders()->firstOrFail();

        $this->assertSame(1, $coupon->refresh()->used_count);
        $this->assertSame('90.00', $order->total_price);

        app(DemoShippingService::class)->cancel($subOrder);

        $this->assertSame(0, $coupon->refresh()->used_count);
        $this->assertSame('0.00', $order->refresh()->total_price);
        $this->assertSame('cancelled', $subOrder->order->refresh()->status);
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
            'description' => 'منتج لاختبار دورة شركة الشحن التجريبية.',
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
