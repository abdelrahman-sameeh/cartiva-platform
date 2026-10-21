<?php

namespace Tests\Feature\Models;

use App\Models\Order\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class FlexibleProductSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_flexible_product_columns_and_variant_options_are_available(): void
    {
        $this->assertTrue(Schema::hasColumns('product_variants', [
            'sku',
            'price',
            'stock',
            'option_signature',
        ]));
        $this->assertTrue(Schema::hasColumns('product_attributes', [
            'value',
            'numeric_value',
            'unit',
        ]));
        $this->assertTrue(Schema::hasColumns('order_items', [
            'variant_sku',
            'variant_options',
        ]));
        $this->assertTrue(Schema::hasTable('product_variant_options'));

        $seller = User::create([
            'first_name' => 'Flexible',
            'last_name' => 'Seller',
            'email' => 'flexible-seller@example.test',
            'password' => 'password123',
        ]);
        $product = Product::create([
            'seller_id' => $seller->id,
            'title' => 'Flexible Phone',
            'description' => 'A product used to verify the flexible catalog schema.',
            'price' => 15000,
            'discount_amount' => 500,
        ]);
        $signature = hash('sha256', json_encode([
            'color' => 'black',
            'storage' => '256gb',
        ], JSON_THROW_ON_ERROR));
        $variant = $product->variants()->create([
            'sku' => 'PHONE-256-BLK',
            'size' => 'legacy',
            'color' => 'legacy',
            'price' => 16000,
            'stock' => 7,
            'option_signature' => $signature,
        ]);
        $variant->options()->createMany([
            ['key' => 'storage', 'value' => '256GB'],
            ['key' => 'color', 'value' => 'Black'],
        ]);
        $product->attributes()->create([
            'key' => 'weight',
            'value' => '190 g',
            'numeric_value' => 190,
            'unit' => 'g',
        ]);

        $this->assertEqualsCanonicalizing(['storage', 'color'], $variant->options()->pluck('key')->all());
        $this->assertSame('190.0000', $product->attributes()->firstOrFail()->numeric_value);

        $this->expectException(QueryException::class);

        $product->variants()->create([
            'size' => 'another legacy value',
            'color' => 'another legacy value',
            'stock' => 1,
            'option_signature' => $signature,
        ]);
    }

    public function test_order_item_casts_variant_options_as_a_snapshot_array(): void
    {
        $item = (new OrderItem)->forceFill([
            'variant_options' => [
                'storage' => '256GB',
                'color' => 'Black',
            ],
        ]);

        $this->assertSame([
            'storage' => '256GB',
            'color' => 'Black',
        ], $item->variant_options);
    }
}
