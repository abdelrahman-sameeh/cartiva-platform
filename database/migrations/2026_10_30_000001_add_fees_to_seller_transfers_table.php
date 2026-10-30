<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('seller_transfers', function (Blueprint $table) {
            $table->unsignedBigInteger('stripe_fee_minor')->nullable()->after('settlement_currency');
            $table->unsignedBigInteger('platform_fee_minor')->nullable()->after('stripe_fee_minor');
            $table->unsignedSmallInteger('platform_fee_bps')->nullable()->after('platform_fee_minor');
            $table->unsignedBigInteger('transfer_amount_minor')->nullable()->after('platform_fee_bps');
        });
    }

    public function down(): void
    {
        Schema::table('seller_transfers', function (Blueprint $table) {
            $table->dropColumn([
                'stripe_fee_minor',
                'platform_fee_minor',
                'platform_fee_bps',
                'transfer_amount_minor',
            ]);
        });
    }
};
