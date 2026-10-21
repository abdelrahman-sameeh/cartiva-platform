<?php

namespace Tests\Feature\Web;

use App\Enums\RoleName;
use App\Models\Address;
use App\Models\Order\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\OrderService;
use App\Services\Payments\Gateways\StripeCheckoutGateway;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Stripe\Checkout\Session;
use Tests\TestCase;

class OrderWebTest extends TestCase
{
    use RefreshDatabase;

    public function test_buyer_can_complete_checkout_and_view_the_order(): void
    {
        [$buyer, , $product, $address] = $this->prepareCart();

        $this->actingAs($buyer)
            ->get(route('orders.create'))
            ->assertOk()
            ->assertSee('إتمام الطلب')
            ->assertSee($product->title);

        $this->actingAs($buyer)
            ->post(route('orders.store'), [
                'phone' => '01012345678',
                'address_id' => $address->id,
                'payment_method' => 'cash',
            ])
            ->assertRedirect();

        $order = Order::query()->where('user_id', $buyer->id)->firstOrFail();

        $this->actingAs($buyer)
            ->get(route('orders.show', $order))
            ->assertOk()
            ->assertSee('الطلب #'.$order->id)
            ->assertSee($product->title)
            ->assertSee('200.00');

        $this->actingAs($buyer)
            ->get(route('orders.index'))
            ->assertOk()
            ->assertSee('#'.$order->id);
    }

    public function test_empty_cart_cannot_open_checkout(): void
    {
        $buyer = $this->createUser('empty-cart@example.com');

        $this->actingAs($buyer)
            ->get(route('orders.create'))
            ->assertRedirect(route('cart.index'))
            ->assertSessionHasErrors('cart');
    }

    public function test_buyer_cannot_view_another_buyers_order(): void
    {
        [$buyer, , , $address] = $this->prepareCart();
        $order = app(OrderService::class)->create($buyer, [
            'phone' => '01012345678',
            'address_id' => $address->id,
            'payment_method' => 'cash',
        ]);
        $otherBuyer = $this->createUser('other-buyer@example.com');

        $this->actingAs($otherBuyer)
            ->get(route('orders.show', $order))
            ->assertForbidden();
    }

    public function test_buyer_can_start_stripe_checkout_for_their_card_order(): void
    {
        [$buyer, , , $address] = $this->prepareCart();
        $order = app(OrderService::class)->create($buyer, [
            'phone' => '01012345678',
            'address_id' => $address->id,
            'payment_method' => 'card',
        ]);
        $gateway = Mockery::mock(StripeCheckoutGateway::class);
        $gateway->shouldReceive('createSession')->once()->andReturn(Session::constructFrom([
            'id' => 'cs_test_web_order',
            'object' => 'checkout.session',
            'status' => 'open',
            'payment_status' => 'unpaid',
            'url' => 'https://checkout.stripe.test/web-order',
        ]));
        $this->app->instance(StripeCheckoutGateway::class, $gateway);

        $this->actingAs($buyer)
            ->get(route('orders.show', $order))
            ->assertOk()
            ->assertSee('الدفع الآمن عبر Stripe');

        $this->actingAs($buyer)
            ->post(route('orders.payment.store', $order))
            ->assertRedirect('https://checkout.stripe.test/web-order');

        $this->assertDatabaseHas('payments', [
            'order_id' => $order->id,
            'checkout_session_id' => 'cs_test_web_order',
            'status' => 'pending',
        ]);
    }

    public function test_checkout_return_does_not_mark_the_order_paid(): void
    {
        [$buyer, , , $address] = $this->prepareCart();
        $order = app(OrderService::class)->create($buyer, [
            'phone' => '01012345678',
            'address_id' => $address->id,
            'payment_method' => 'card',
        ]);

        $this->actingAs($buyer)
            ->get(route('orders.payment.return', [
                'order' => $order,
                'session_id' => 'cs_fake',
            ]))
            ->assertRedirect(route('orders.show', $order));

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'payment_status' => 'pending',
        ]);
    }

    public function test_buyer_cannot_start_checkout_for_another_buyers_order(): void
    {
        [$buyer, , , $address] = $this->prepareCart();
        $order = app(OrderService::class)->create($buyer, [
            'phone' => '01012345678',
            'address_id' => $address->id,
            'payment_method' => 'card',
        ]);
        $otherBuyer = $this->createUser('checkout-attacker@example.test');

        $this->actingAs($otherBuyer)
            ->post(route('orders.payment.store', $order))
            ->assertForbidden();

        $this->assertDatabaseCount('payments', 0);
    }

    public function test_seller_can_view_and_update_their_product_order(): void
    {
        [$buyer, $seller, , $address] = $this->prepareCart();
        $order = app(OrderService::class)->create($buyer, [
            'phone' => '01012345678',
            'address_id' => $address->id,
            'payment_method' => 'cash',
        ]);
        $subOrder = $order->subOrders->first();

        $this->actingAs($seller)
            ->get(route('seller.orders.index'))
            ->assertOk()
            ->assertSee('طلبات المنتجات')
            ->assertSee('حالة الدفع')
            ->assertSee('في انتظار الدفع')
            ->assertSee($buyer->first_name);

        $this->actingAs($seller)
            ->get(route('seller.orders.show', $subOrder))
            ->assertOk()
            ->assertSee('تيشيرت طلب Web')
            ->assertSee('XL')
            ->assertSee('كحلي')
            ->assertSee('بيانات الدفع')
            ->assertSee('في انتظار الدفع')
            ->assertSee('الدفع عند الاستلام');

        $this->actingAs($seller)
            ->put(route('seller.orders.status.update', $subOrder), ['status' => 'shipped'])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('sub_orders', ['id' => $subOrder->id, 'status' => 'shipped']);
        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 'shipped']);
    }

    private function prepareCart(): array
    {
        $buyer = $this->createUser('buyer-web-order@example.com');
        $seller = $this->createSeller('seller-web-order@example.com');
        $product = Product::create([
            'title' => 'تيشيرت طلب Web',
            'description' => 'وصف المنتج المستخدم في اختبار طلبات الويب.',
            'price' => 100,
            'discount_amount' => 0,
            'seller_id' => $seller->id,
            'is_active' => true,
        ]);
        $variant = $product->variants()->create([
            'size' => 'XL',
            'color' => 'كحلي',
            'stock' => 10,
        ]);
        $address = Address::create([
            'user_id' => $buyer->id,
            'country' => 'EG',
            'city' => 'القاهرة',
            'street' => 'شارع التحرير رقم 10',
            'is_default' => true,
        ]);
        $cart = $buyer->cart()->create();
        $cart->items()->create([
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
            'quantity' => 2,
        ]);

        return [$buyer, $seller, $product, $address];
    }

    private function createUser(string $email): User
    {
        return User::create([
            'first_name' => 'مشتري',
            'last_name' => 'تجريبي',
            'email' => $email,
            'password' => 'password123',
        ]);
    }

    private function createSeller(string $email): User
    {
        $seller = $this->createUser($email);
        $seller->syncRoles(RoleName::SELLER);

        return $seller;
    }
}
