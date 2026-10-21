<?php

namespace Tests\Feature\Api;

use App\Enums\RoleName;
use App\Models\Address;
use App\Models\Coupon;
use App\Models\Order\Order;
use App\Models\Product;
use App\Models\User;
use App\Support\ProductVariantOptions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class OrderApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_buyer_can_create_and_view_an_order_through_the_api(): void
    {
        [$buyer, $seller, $product, $address, $coupon, $variant] = $this->prepareCart();
        Sanctum::actingAs($buyer);

        $orderId = $this->postJson('/api/orders', [
            'phone' => '01012345678',
            'address_id' => $address->id,
            'payment_method' => 'cash',
        ])
            ->assertCreated()
            ->assertJsonPath('order.user_id', $buyer->id)
            ->assertJsonPath('order.subtotal', '220.00')
            ->assertJsonPath('order.discount', '22.00')
            ->assertJsonPath('order.total_price', '198.00')
            ->assertJsonPath('order.sub_orders.0.seller_id', $seller->id)
            ->assertJsonPath('order.sub_orders.0.items.0.title', 'تيشيرت طلب API')
            ->json('order.id');

        $this->assertDatabaseHas('order_items', [
            'title' => 'تيشيرت طلب API',
            'price_at_purchase' => 110,
            'variant_sku' => 'PHONE-256-BLK',
            'size' => null,
            'color' => null,
            'quantity' => 2,
        ]);
        $orderItem = Order::query()->findOrFail($orderId)->subOrders()->firstOrFail()->items()->firstOrFail();
        $this->assertSame(['color' => 'أسود', 'storage' => '256 GB'], $orderItem->variant_options);
        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'sold_count' => 2,
        ]);
        $this->assertDatabaseHas('coupons', ['id' => $coupon->id, 'used_count' => 1]);
        $this->assertDatabaseHas('product_variants', ['id' => $variant->id, 'stock' => 8]);
        $this->assertDatabaseCount('cart_items', 0);
        $this->assertDatabaseCount('cart_coupons', 0);

        $this->getJson('/api/orders')
            ->assertOk()
            ->assertJsonCount(1, 'orders');

        $this->getJson("/api/orders/{$orderId}")
            ->assertOk()
            ->assertJsonPath('order.address.city', 'القاهرة');
    }

    public function test_user_cannot_view_another_users_order_through_the_api(): void
    {
        [$buyer, , , $address] = $this->prepareCart();
        Sanctum::actingAs($buyer);
        $orderId = $this->postJson('/api/orders', [
            'phone' => '01012345678',
            'address_id' => $address->id,
            'payment_method' => 'cash',
        ])->json('order.id');

        Sanctum::actingAs($this->createUser('other-buyer@example.com'));

        $this->getJson("/api/orders/{$orderId}")->assertForbidden();
    }

    public function test_seller_can_view_and_update_their_sub_order_status_through_the_api(): void
    {
        [$buyer, $seller, , $address] = $this->prepareCart();
        Sanctum::actingAs($buyer);
        $orderId = $this->postJson('/api/orders', [
            'phone' => '01012345678',
            'address_id' => $address->id,
            'payment_method' => 'cash',
        ])->json('order.id');
        $order = Order::query()->findOrFail($orderId);
        $subOrder = $order->subOrders()->firstOrFail();

        Sanctum::actingAs($seller);

        $this->getJson('/api/seller/orders')
            ->assertOk()
            ->assertJsonCount(1, 'sub_orders');

        $this->getJson("/api/seller/sub-orders/{$subOrder->id}")
            ->assertOk()
            ->assertJsonPath('sub_order.items.0.title', 'تيشيرت طلب API');

        $this->putJson("/api/seller/sub-orders/{$subOrder->id}/status", ['status' => 'processing'])
            ->assertOk()
            ->assertJsonPath('sub_order.status', 'processing');

        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 'processing']);

        $otherSeller = $this->createSeller('other-seller@example.com');
        Sanctum::actingAs($otherSeller);

        $this->putJson("/api/seller/sub-orders/{$subOrder->id}/status", ['status' => 'shipped'])
            ->assertForbidden();
    }

    private function prepareCart(): array
    {
        $buyer = $this->createUser('buyer-order@example.com');
        $seller = $this->createSeller('seller-order@example.com');
        $product = Product::create([
            'title' => 'تيشيرت طلب API',
            'description' => 'وصف المنتج المستخدم في اختبار إنشاء الطلب.',
            'price' => 100,
            'discount_amount' => 10,
            'seller_id' => $seller->id,
            'is_active' => true,
        ]);
        $variant = $product->variants()->create([
            'sku' => 'PHONE-256-BLK',
            'price' => 120,
            'stock' => 10,
            'option_signature' => ProductVariantOptions::signature(['storage' => '256 GB', 'color' => 'أسود']),
        ]);
        $variant->options()->createMany([
            ['key' => 'storage', 'value' => '256 GB'],
            ['key' => 'color', 'value' => 'أسود'],
        ]);
        $address = Address::create([
            'user_id' => $buyer->id,
            'country' => 'EG',
            'city' => 'القاهرة',
            'street' => 'شارع التحرير رقم 10',
            'is_default' => true,
        ]);
        $coupon = Coupon::create([
            'code' => 'ORDER10',
            'percentage' => 10,
            'expire_date' => now()->addMonth()->toDateString(),
            'max_usage' => 10,
            'used_count' => 0,
            'seller_id' => $seller->id,
            'is_active' => true,
        ]);
        $cart = $buyer->cart()->create();
        $cart->items()->create([
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
            'quantity' => 2,
        ]);
        $cart->coupons()->create(['coupon_id' => $coupon->id]);

        return [$buyer, $seller, $product, $address, $coupon, $variant];
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
