<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shipments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sub_order_id')->unique()->constrained('sub_orders')->cascadeOnDelete();
            $table->string('provider', 50)->default('demo_shipping');
            $table->string('tracking_number', 50)->unique();
            $table->enum('status', [
                'created',
                'picked_up',
                'in_transit',
                'delivered',
                'returned',
                'cancelled',
            ])->default('created');
            $table->enum('cod_status', [
                'not_applicable',
                'awaiting_collection',
                'collected',
                'remitted',
                'refunded',
            ])->default('not_applicable');
            $table->decimal('cod_amount', 10, 2)->default(0);
            $table->decimal('platform_fee', 10, 2)->default(0);
            $table->decimal('shipping_fee', 10, 2)->default(0);
            $table->decimal('collection_fee', 10, 2)->default(0);
            $table->decimal('seller_net', 10, 2)->default(0);
            $table->timestamp('picked_up_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('cod_collected_at')->nullable();
            $table->timestamp('cod_remitted_at')->nullable();
            $table->timestamp('seller_paid_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'cod_status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipments');
    }
};
