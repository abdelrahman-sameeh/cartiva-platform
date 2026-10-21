<?php

namespace Tests\Feature\Api;

use App\Enums\RoleName;
use App\Models\Product;
use App\Models\User;
use App\Support\ProductVariantOptions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CartApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_cart_api_still_uses_the_same_endpoints(): void
    {
        $buyer = $this->createBuyer();
        $product = $this->createProduct($this->createSeller());
        $variant = $product->variants()->firstOrFail();
        Sanctum::actingAs($buyer);

        $this->postJson('/api/cart/items', [
            'items' => [['variant_id' => $variant->id, 'quantity' => 2]],
        ])
            ->assertCreated()
            ->assertJsonPath('cart.items_count', 1)
            ->assertJsonPath('cart.summary_cart.total', 150);

        $this->getJson('/api/cart')
            ->assertOk()
            ->assertJsonPath('cart.groups.0.items.0.product.title', 'منتج API')
            ->assertJsonPath('cart.groups.0.items.0.variant.options.storage', '128 GB')
            ->assertJsonPath('cart.groups.0.items.0.variant.options.color', 'أسود')
            ->assertJsonPath('cart.groups.0.items.0.variant.label', 'color: أسود • storage: 128 GB')
            ->assertJsonCount(1, 'cart.groups.0.items.0.product.variants');

        $largeBlue = $product->variants()->create([
            'stock' => 3,
            'option_signature' => ProductVariantOptions::signature(['storage' => '256 GB', 'color' => 'أزرق']),
        ]);
        $largeBlue->options()->createMany([
            ['key' => 'storage', 'value' => '256 GB'],
            ['key' => 'color', 'value' => 'أزرق'],
        ]);
        $cartItem = $buyer->cart()->firstOrFail()->items()->firstOrFail();

        $this->putJson("/api/cart/items/{$cartItem->id}", [
            'variant_id' => $largeBlue->id,
            'quantity' => 1,
        ])
            ->assertOk()
            ->assertJsonPath('data.product_variant_id', $largeBlue->id)
            ->assertJsonPath('data.quantity', 1);

        $this->postJson('/api/cart/validate')
            ->assertOk()
            ->assertJsonPath('valid', true);
    }

    private function createBuyer(): User
    {
        return User::create([
            'first_name' => 'مشتري',
            'last_name' => 'تجريبي',
            'email' => 'api-buyer@example.com',
            'password' => 'password123',
        ]);
    }

    private function createSeller(): User
    {
        $seller = User::create([
            'first_name' => 'بائع',
            'last_name' => 'تجريبي',
            'email' => 'api-seller@example.com',
            'password' => 'password123',
        ]);
        $seller->syncRoles(RoleName::SELLER);

        return $seller;
    }

    private function createProduct(User $seller): Product
    {
        $product = Product::create([
            'title' => 'منتج API',
            'description' => 'وصف مناسب للمنتج المستخدم في اختبار السلة.',
            'price' => 75,
            'discount_amount' => 0,
            'seller_id' => $seller->id,
            'is_active' => true,
        ]);
        $variant = $product->variants()->create([
            'stock' => 10,
            'option_signature' => ProductVariantOptions::signature(['storage' => '128 GB', 'color' => 'أسود']),
        ]);
        $variant->options()->createMany([
            ['key' => 'storage', 'value' => '128 GB'],
            ['key' => 'color', 'value' => 'أسود'],
        ]);

        return $product;
    }
}
