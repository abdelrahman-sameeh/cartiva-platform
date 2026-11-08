<?php

namespace Tests\Feature\Api;

use App\Enums\RoleName;
use App\Models\Category;
use App\Models\SubCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ProductApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_stock_is_stored_only_on_product_variants(): void
    {
        $this->assertFalse(Schema::hasColumn('products', 'quantity'));
        $this->assertTrue(Schema::hasColumn('product_variants', 'stock'));
        $this->assertFalse(Schema::hasColumn('product_variants', 'quantity'));
    }

    public function test_seller_can_create_and_update_product_variants_through_the_api(): void
    {
        Storage::fake('public');
        $seller = User::create([
            'first_name' => 'بائع',
            'last_name' => 'تجريبي',
            'email' => 'variant-seller@example.com',
            'password' => 'password123',
        ])->syncRoles(RoleName::SELLER);
        $category = Category::create(['title' => 'ملابس']);
        $subCategory = SubCategory::create([
            'title' => 'تيشيرتات',
            'category_id' => $category->id,
        ]);
        Sanctum::actingAs($seller);

        $response = $this->post('/api/products', [
            'title' => 'هاتف متعدد السعات',
            'description' => 'هاتف متاح بأكثر من سعة تخزين ولون.',
            'price' => 300,
            'discount_amount' => 25,
            'cover_image' => $this->fakeImage(),
            'sub_categories' => [$subCategory->id],
            'variants' => [
                [
                    'sku' => 'PHONE-128-BLK',
                    'stock' => 5,
                    'options' => [
                        ['key' => 'Storage', 'value' => '128 GB'],
                        ['key' => 'Color', 'value' => 'أسود'],
                    ],
                ],
                [
                    'sku' => 'PHONE-256-WHT',
                    'price' => 350,
                    'stock' => 3,
                    'options' => [
                        ['key' => 'Storage', 'value' => '256 GB'],
                        ['key' => 'Color', 'value' => 'أبيض'],
                    ],
                ],
            ],
        ])
            ->assertCreated()
            ->assertJsonPath('data.stock', 8)
            ->assertJsonFragment(['sku' => 'PHONE-128-BLK', 'stock' => 5])
            ->assertJsonFragment(['storage' => '256 GB', 'color' => 'أبيض']);
        $productId = $response->json('data.id');

        $this->patchJson("/api/products/{$productId}", [
            'variants' => [
                [
                    'sku' => 'PHONE-512-NVY',
                    'stock' => 4,
                    'options' => [
                        ['key' => 'Storage', 'value' => '512 GB'],
                        ['key' => 'Color', 'value' => 'كحلي'],
                    ],
                ],
            ],
        ])
            ->assertOk()
            ->assertJsonPath('stock', 4)
            ->assertJsonCount(1, 'variants')
            ->assertJsonPath('variants.0.options.storage', '512 GB')
            ->assertJsonPath('variants.0.options.color', 'كحلي');

        $this->assertDatabaseMissing('product_variants', [
            'product_id' => $productId,
            'sku' => 'PHONE-128-BLK',
        ]);
        $this->assertDatabaseHas('product_variants', [
            'product_id' => $productId,
            'sku' => 'PHONE-512-NVY',
            'stock' => 4,
        ]);
        $this->assertDatabaseHas('product_variant_options', [
            'key' => 'storage',
            'value' => '512 GB',
        ]);
    }

    public function test_duplicate_variant_option_combination_is_rejected(): void
    {
        $seller = User::create([
            'first_name' => 'بائع',
            'last_name' => 'تجريبي',
            'email' => 'duplicate-variant@example.com',
            'password' => 'password123',
        ])->syncRoles(RoleName::SELLER);
        $category = Category::create(['title' => 'ملابس مكررة']);
        $subCategory = SubCategory::create([
            'title' => 'قمصان',
            'category_id' => $category->id,
        ]);
        Sanctum::actingAs($seller);

        $this->post('/api/products', [
            'title' => 'منتج بتكرار',
            'description' => 'وصف صالح لاختبار منع تكرار الاختيارات.',
            'price' => 100,
            'cover_image' => $this->fakeImage(),
            'sub_categories' => [$subCategory->id],
            'variants' => [
                [
                    'stock' => 2,
                    'options' => [
                        ['key' => 'Size', 'value' => 'M'],
                        ['key' => 'Color', 'value' => 'أسود'],
                    ],
                ],
                [
                    'stock' => 3,
                    'options' => [
                        ['key' => 'color', 'value' => ' أسود '],
                        ['key' => 'size', 'value' => 'm'],
                    ],
                ],
            ],
        ], ['Accept' => 'application/json'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['variants.1.options']);
    }

    public function test_seller_can_create_a_product_without_options_using_a_default_variant(): void
    {
        Storage::fake('public');
        $seller = User::create([
            'first_name' => 'Simple',
            'last_name' => 'Seller',
            'email' => 'simple-product@example.com',
            'password' => 'password123',
        ])->syncRoles(RoleName::SELLER);
        $category = Category::create(['title' => 'كتب']);
        $subCategory = SubCategory::create(['title' => 'روايات', 'category_id' => $category->id]);
        Sanctum::actingAs($seller);

        $this->post('/api/products', [
            'title' => 'رواية ورقية',
            'description' => 'منتج بسيط لا يحتاج إلى أي اختيارات إضافية.',
            'price' => 120,
            'cover_image' => $this->fakeImage(),
            'sub_categories' => [$subCategory->id],
            'variants' => [['sku' => 'BOOK-001', 'stock' => 9, 'options' => []]],
        ])->assertCreated()
            ->assertJsonPath('data.variants.0.option_label', 'المنتج الأساسي')
            ->assertJsonPath('data.variants.0.options', []);
    }

    private function fakeImage(): UploadedFile
    {
        return UploadedFile::fake()->createWithContent(
            'shirt.png',
            base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=')
        );
    }
}
