<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    /**
     * An order and its per-seller sub orders move through the same states.
     */
    private const STATUSES = [
        'pending',
        'processing',
        'shipped',
        'completed',
        'cancelled',
    ];

    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->decimal('subtotal', 10, 2);
            $table->decimal('discount', 10, 2)->default(0);
            $table->decimal('total_price', 10, 2);
            $table->string('phone', 15);
            $table->foreignId('address_id')->nullable()->constrained('addresses')->nullOnDelete();
            $table->enum('status', self::STATUSES)->default('pending');
            $table->enum('payment_status', [
                'pending',
                'paid',
                'failed',
                'refunded',
            ])->default('pending');
            $table->enum('payment_method', [
                'cash',
                'card',
                'wallet',
            ])->default('cash');
            $table->timestamps();

            // "my orders", filtered by state - status alone is too low
            // cardinality to be worth an index of its own.
            $table->index(['user_id', 'status']);
            $table->index('payment_status');
        });

        Schema::create('sub_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->foreignId('seller_id')->constrained('users')->cascadeOnDelete();
            $table->decimal('subtotal', 10, 2);
            $table->decimal('discount', 10, 2)->default(0);
            $table->decimal('total_price', 10, 2);
            $table->enum('status', self::STATUSES)->default('pending');
            $table->timestamps();

            // The seller dashboard reads its own sub orders by state.
            $table->index(['seller_id', 'status']);
            // One sub order per seller per order.
            $table->unique(['order_id', 'seller_id']);
        });

        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sub_order_id')->constrained('sub_orders')->cascadeOnDelete();
            // Kept only as a back reference: the columns below are a snapshot,
            // so the line survives the product being deleted or edited.
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->string('title', 255);
            $table->string('slug', 255);
            $table->text('description');
            $table->string('cover_image', 300)->nullable();
            $table->decimal('price_at_purchase', 10, 2);
            $table->string('variant_sku', 100)->nullable();
            $table->json('variant_options')->nullable();
            $table->string('size', 30)->nullable();
            $table->string('color', 50)->nullable();
            $table->unsignedInteger('quantity');
            // Copied from products.created_at, which is itself nullable.
            $table->timestamp('created_at_snapshot')->nullable();
        });

        Schema::create('order_item_pictures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_item_id')->constrained('order_items')->cascadeOnDelete();
            $table->string('image_path', 300);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_item_pictures');
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('sub_orders');
        Schema::dropIfExists('orders');
    }
};
