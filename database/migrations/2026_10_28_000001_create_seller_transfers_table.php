<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('seller_transfers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sub_order_id')->unique()->constrained('sub_orders')->cascadeOnDelete();
            $table->foreignId('payment_id')->constrained('payments')->cascadeOnDelete();
            $table->foreignId('seller_id')->constrained('users')->cascadeOnDelete();
            $table->string('stripe_account_id')->nullable();
            $table->string('stripe_transfer_id')->nullable()->unique();
            $table->uuid('idempotency_key')->unique();
            $table->unsignedBigInteger('amount_minor');
            $table->char('currency', 3);
            $table->string('status')->default('pending');
            $table->text('failure_reason')->nullable();
            $table->timestamp('attempted_at')->nullable();
            $table->timestamp('transferred_at')->nullable();
            $table->timestamps();

            $table->index(['seller_id', 'status']);
            $table->index(['payment_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seller_transfers');
    }
};
