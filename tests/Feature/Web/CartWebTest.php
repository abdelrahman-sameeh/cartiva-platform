<?php

namespace Tests\Feature\Web;

use App\Enums\RoleName;
use App\Models\Coupon;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CartWebTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_buyers_can_open_the_cart(): void
    {
        $seller = $this->createSeller();

        $this->actingAs($seller)
            ->get(route('cart.index'))
            ->assertForbidden();
    }

    public function test_buyer_can_add_a_product_and_view_the_cart(): void
    {
        $buyer = $this->createBuyer('buyer@example.com');
        $product = $this->createProduct($this->createSeller(), 'تيشيرت للاختبار', 150);
        $variant = $product->variants()->firstOrFail();

        $this->actingAs($buyer)
            ->post(route('cart.items.store'), [
                'product_variant_id' => $variant->id,
                'quantity' => 2,
            ])
            ->assertRedirect(route('cart.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('cart_items', [
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
            'quantity' => 2,
        ]);

        $this->actingAs($buyer)
            ->get(route('cart.index'))
            ->assertOk()
            ->assertSee('تيشيرت للاختبار')
            ->assertSee('M')
            ->assertSee('أسود')
            ->assertSee('300.00');
    }

    public function test_each_size_and_color_has_independent_stock_in_the_cart(): void
    {
        $buyer = $this->createBuyer('variants-buyer@example.com');
        $product = $this->createProduct($this->createSeller(), 'تيشيرت متعدد الاختيارات');
        $mediumBlack = $product->variants()->firstOrFail();
        $largeBlue = $product->variants()->create([
            'size' => 'L',
            'color' => 'أزرق',
            'stock' => 1,
        ]);

        $this->actingAs($buyer)
            ->post(route('cart.items.store'), [
                'product_variant_id' => $largeBlue->id,
                'quantity' => 2,
            ])
            ->assertSessionHasErrors('quantity');

        $this->assertDatabaseMissing('cart_items', ['product_variant_id' => $largeBlue->id]);

        $this->actingAs($buyer)
            ->post(route('cart.items.store'), [
                'product_variant_id' => $mediumBlack->id,
                'quantity' => 2,
            ])
            ->assertRedirect(route('cart.index'));

        $this->actingAs($buyer)
            ->post(route('cart.items.store'), [
                'product_variant_id' => $largeBlue->id,
                'quantity' => 1,
            ])
            ->assertRedirect(route('cart.index'));

        $this->assertDatabaseCount('cart_items', 2);
    }

    public function test_buyer_can_update_and_remove_their_cart_item(): void
    {
        $buyer = $this->createBuyer('buyer@example.com');
        $product = $this->createProduct($this->createSeller(), 'منتج السلة');
        $cart = $buyer->cart()->create();
        $item = $cart->items()->create([
            'product_id' => $product->id,
            'product_variant_id' => $product->variants()->firstOrFail()->id,
            'quantity' => 1,
        ]);

        $this->actingAs($buyer)
            ->put(route('cart.items.update', $item->id), ['quantity' => 3])
            ->assertRedirect();

        $this->assertDatabaseHas('cart_items', ['id' => $item->id, 'quantity' => 3]);

        $this->actingAs($buyer)
            ->delete(route('cart.items.destroy', $item->id))
            ->assertRedirect();

        $this->assertDatabaseMissing('cart_items', ['id' => $item->id]);
    }

    public function test_buyer_can_change_product_variant_inside_the_cart(): void
    {
        $buyer = $this->createBuyer('change-variant@example.com');
        $product = $this->createProduct($this->createSeller(), 'قميص متعدد المقاسات');
        $mediumBlack = $product->variants()->firstOrFail();
        $largeBlue = $product->variants()->create([
            'size' => 'L',
            'color' => 'أزرق',
            'stock' => 4,
        ]);
        $item = $buyer->cart()->create()->items()->create([
            'product_id' => $product->id,
            'product_variant_id' => $mediumBlack->id,
            'quantity' => 1,
        ]);

        $this->actingAs($buyer)
            ->put(route('cart.items.update', $item->id), [
                'product_variant_id' => $largeBlue->id,
                'quantity' => 2,
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('cart_items', [
            'id' => $item->id,
            'product_id' => $product->id,
            'product_variant_id' => $largeBlue->id,
            'quantity' => 2,
        ]);

        $this->actingAs($buyer)
            ->get(route('cart.index'))
            ->assertOk()
            ->assertSee('cart-variant-selector', false)
            ->assertSee('cart-change-status', false)
            ->assertSee('حفظ التغيير')
            ->assertSee('أزرق')
            ->assertSee('L');
    }

    public function test_buyer_cannot_replace_a_cart_item_with_another_products_variant(): void
    {
        $buyer = $this->createBuyer('foreign-variant@example.com');
        $seller = $this->createSeller();
        $product = $this->createProduct($seller, 'المنتج الأصلي');
        $otherProduct = $this->createProduct($seller, 'منتج آخر');
        $originalVariant = $product->variants()->firstOrFail();
        $foreignVariant = $otherProduct->variants()->firstOrFail();
        $item = $buyer->cart()->create()->items()->create([
            'product_id' => $product->id,
            'product_variant_id' => $originalVariant->id,
            'quantity' => 1,
        ]);

        $this->actingAs($buyer)
            ->put(route('cart.items.update', $item->id), [
                'product_variant_id' => $foreignVariant->id,
                'quantity' => 1,
            ])
            ->assertSessionHasErrors('product_variant_id');

        $this->assertDatabaseHas('cart_items', [
            'id' => $item->id,
            'product_variant_id' => $originalVariant->id,
        ]);
    }

    public function test_buyer_cannot_manage_another_buyers_cart_item(): void
    {
        $owner = $this->createBuyer('owner@example.com');
        $otherBuyer = $this->createBuyer('other@example.com');
        $product = $this->createProduct($this->createSeller(), 'منتج خاص');
        $item = $owner->cart()->create()->items()->create([
            'product_id' => $product->id,
            'product_variant_id' => $product->variants()->firstOrFail()->id,
            'quantity' => 1,
        ]);
        $otherBuyer->cart()->create();

        $this->actingAs($otherBuyer)
            ->delete(route('cart.items.destroy', $item->id))
            ->assertNotFound();

        $this->assertDatabaseHas('cart_items', ['id' => $item->id]);
    }

    public function test_buyer_can_apply_a_valid_coupon(): void
    {
        $buyer = $this->createBuyer('buyer@example.com');
        $seller = $this->createSeller();
        $product = $this->createProduct($seller, 'منتج بخصم', 100);
        $cart = $buyer->cart()->create();
        $cart->items()->create([
            'product_id' => $product->id,
            'product_variant_id' => $product->variants()->firstOrFail()->id,
            'quantity' => 1,
        ]);
        $coupon = $this->createCoupon($seller, 'SAVE10');

        $this->actingAs($buyer)
            ->post(route('cart.coupons.store'), ['code' => 'save10'])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('cart_coupons', [
            'cart_id' => $cart->id,
            'coupon_id' => $coupon->id,
        ]);

        $this->actingAs($buyer)
            ->get(route('cart.index'))
            ->assertOk()
            ->assertSee('SAVE10')
            ->assertSee('90.00');
    }

    private function createBuyer(string $email): User
    {
        return User::create([
            'first_name' => 'مشتري',
            'last_name' => 'تجريبي',
            'email' => $email,
            'password' => 'password123',
        ]);
    }

    private function createSeller(): User
    {
        $seller = User::create([
            'first_name' => 'بائع',
            'last_name' => 'تجريبي',
            'email' => fake()->unique()->safeEmail(),
            'password' => 'password123',
        ]);
        $seller->syncRoles(RoleName::SELLER);

        return $seller;
    }

    private function createProduct(User $seller, string $title, float $price = 100): Product
    {
        $product = Product::create([
            'title' => $title,
            'description' => 'وصف مناسب للمنتج المستخدم في اختبار السلة.',
            'price' => $price,
            'discount_amount' => 0,
            'seller_id' => $seller->id,
            'is_active' => true,
        ]);
        $product->variants()->create([
            'size' => 'M',
            'color' => 'أسود',
            'stock' => 10,
        ]);

        return $product;
    }

    private function createCoupon(User $seller, string $code): Coupon
    {
        return Coupon::create([
            'code' => $code,
            'percentage' => 10,
            'expire_date' => now()->addMonth()->toDateString(),
            'max_usage' => 10,
            'used_count' => 0,
            'seller_id' => $seller->id,
            'is_active' => true,
        ]);
    }
}
