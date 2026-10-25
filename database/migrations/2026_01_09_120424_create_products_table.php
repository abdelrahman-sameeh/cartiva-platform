<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('title', 255);
            $table->string('slug', 255);
            $table->text('description');
            $table->string('cover_image')->nullable();
            $table->decimal('price', 10, 2);
            $table->decimal('discount_amount', 10, 2)->default(0);
            $table->foreignId('seller_id')->constrained('users')->cascadeOnDelete();
            $table->boolean('is_active')->default(true);
            $table->integer('sold_count')->default(0);
            $table->decimal('rating_avg', 2, 1)->default(0);
            $table->integer('rating_count')->default(0);
            $table->timestamps();

            $table->unique(['seller_id', 'slug']);
            $table->index(['is_active', 'created_at'], 'products_active_created_index');
            $table->index(
                ['seller_id', 'is_active', 'created_at'],
                'products_seller_active_created_index'
            );
        });

        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->string('sku', 100)->nullable()->index();
            $table->string('size', 30)->nullable();
            $table->string('color', 50)->nullable();
            $table->decimal('price', 10, 2)->nullable();
            $table->unsignedInteger('stock')->default(0);
            $table->char('option_signature', 64)->nullable();
            $table->timestamps();

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

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_variants');
        Schema::dropIfExists('products');
    }
};
