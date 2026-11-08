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
        Schema::create('seller_stripe_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('seller_id')->unique()->constrained('users')->restrictOnDelete();
            $table->string('stripe_account_id')->unique();
            $table->string('onboarding_status')->default('pending');
            $table->string('payouts_status')->nullable();
            $table->string('transfers_status')->nullable();
            $table->json('requirements_due')->nullable();
            $table->string('disabled_reason')->nullable();
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('seller_stripe_accounts');
    }
};
