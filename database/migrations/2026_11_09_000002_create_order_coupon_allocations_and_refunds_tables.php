<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_coupon_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->foreignId('sub_order_id')->unique()->constrained('sub_orders')->cascadeOnDelete();
            $table->foreignId('coupon_id')->nullable()->constrained('coupons')->nullOnDelete();
            $table->decimal('discount', 10, 2);
            $table->timestamp('released_at')->nullable();
            $table->timestamps();
        });

        Schema::create('refunds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->foreignId('sub_order_id')->unique()->constrained('sub_orders')->cascadeOnDelete();
            $table->foreignId('payment_id')->constrained('payments')->cascadeOnDelete();
            $table->string('provider_refund_id')->nullable()->unique();
            $table->uuid('idempotency_key')->unique();
            $table->unsignedBigInteger('amount_minor');
            $table->char('currency', 3);
            $table->string('status')->default('pending');
            $table->string('failure_reason', 1000)->nullable();
            $table->timestamp('refunded_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('refunds');
        Schema::dropIfExists('order_coupon_allocations');
    }
};
