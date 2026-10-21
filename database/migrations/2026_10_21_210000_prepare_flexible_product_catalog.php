<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_variants', function (Blueprint $table) {
            $table->string('sku', 100)->nullable()->after('product_id')->index();
            $table->decimal('price', 10, 2)->nullable()->after('color');
            $table->char('option_signature', 64)->nullable()->after('stock');
        });

        Schema::table('product_attributes', function (Blueprint $table) {
            $table->decimal('numeric_value', 15, 4)->nullable()->after('value');
            $table->string('unit', 30)->nullable()->after('numeric_value');
        });

        Schema::table('product_attributes', function (Blueprint $table) {
            $table->string('value', 255)->change();
            $table->unique(['product_id', 'key', 'value'], 'product_attributes_product_key_value_unique');
            $table->index(['key', 'value', 'product_id'], 'product_attributes_key_value_product_index');
            $table->index(
                ['key', 'unit', 'numeric_value', 'product_id'],
                'product_attributes_numeric_filter_index'
            );
        });

        Schema::create('product_variant_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_variant_id')->constrained('product_variants')->cascadeOnDelete();
            $table->string('key', 50);
            $table->string('value', 100);

            $table->unique(['product_variant_id', 'key'], 'variant_options_variant_key_unique');
            $table->index(['key', 'value', 'product_variant_id'], 'variant_options_filter_index');
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->string('variant_sku', 100)->nullable()->after('price_at_purchase');
            $table->json('variant_options')->nullable()->after('variant_sku');
        });

        Schema::table('product_sub_category', function (Blueprint $table) {
            $table->unique(
                ['product_id', 'sub_category_id'],
                'product_sub_category_product_sub_unique'
            );
        });

        Schema::table('products', function (Blueprint $table) {
            $table->index(['is_active', 'created_at'], 'products_active_created_index');
            $table->index(
                ['seller_id', 'is_active', 'created_at'],
                'products_seller_active_created_index'
            );
        });

        $this->backfillVariantOptions();
        $this->backfillOrderItemOptions();

        Schema::table('product_variants', function (Blueprint $table) {
            $table->unique(
                ['product_id', 'option_signature'],
                'product_variants_product_signature_unique'
            );
            $table->index(
                ['product_id', 'stock', 'price'],
                'product_variants_stock_price_index'
            );
        });
    }

    public function down(): void
    {
        Schema::table('product_variants', function (Blueprint $table) {
            $table->dropUnique('product_variants_product_signature_unique');
            $table->dropIndex('product_variants_stock_price_index');
            $table->dropIndex(['sku']);
            $table->dropColumn(['sku', 'price', 'option_signature']);
        });

        Schema::table('product_attributes', function (Blueprint $table) {
            $table->dropUnique('product_attributes_product_key_value_unique');
            $table->dropIndex('product_attributes_key_value_product_index');
            $table->dropIndex('product_attributes_numeric_filter_index');
            $table->dropColumn(['numeric_value', 'unit']);
        });

        Schema::table('product_attributes', function (Blueprint $table) {
            $table->string('value', 50)->change();
        });

        Schema::dropIfExists('product_variant_options');

        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn(['variant_sku', 'variant_options']);
        });

        Schema::table('product_sub_category', function (Blueprint $table) {
            $table->dropUnique('product_sub_category_product_sub_unique');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex('products_active_created_index');
            $table->dropIndex('products_seller_active_created_index');
        });
    }

    private function backfillVariantOptions(): void
    {
        DB::table('product_variants')
            ->select(['id', 'size', 'color'])
            ->orderBy('id')
            ->chunkById(100, function ($variants): void {
                foreach ($variants as $variant) {
                    $options = array_filter([
                        'size' => trim((string) $variant->size),
                        'color' => trim((string) $variant->color),
                    ], fn (string $value): bool => $value !== '');

                    foreach ($options as $key => $value) {
                        DB::table('product_variant_options')->insert([
                            'product_variant_id' => $variant->id,
                            'key' => $key,
                            'value' => $value,
                        ]);
                    }

                    DB::table('product_variants')
                        ->where('id', $variant->id)
                        ->update(['option_signature' => $this->optionSignature($options)]);
                }
            });
    }

    private function backfillOrderItemOptions(): void
    {
        DB::table('order_items')
            ->select(['id', 'size', 'color'])
            ->orderBy('id')
            ->chunkById(100, function ($items): void {
                foreach ($items as $item) {
                    $options = array_filter([
                        'size' => trim((string) $item->size),
                        'color' => trim((string) $item->color),
                    ], fn (string $value): bool => $value !== '');

                    DB::table('order_items')
                        ->where('id', $item->id)
                        ->update([
                            'variant_options' => json_encode(
                                $options,
                                JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR
                            ),
                        ]);
                }
            });
    }

    /** @param array<string, string> $options */
    private function optionSignature(array $options): string
    {
        $normalized = [];

        foreach ($options as $key => $value) {
            $normalized[mb_strtolower(trim($key))] = mb_strtolower(trim($value));
        }

        ksort($normalized, SORT_STRING);

        return hash('sha256', json_encode($normalized, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
    }
};
