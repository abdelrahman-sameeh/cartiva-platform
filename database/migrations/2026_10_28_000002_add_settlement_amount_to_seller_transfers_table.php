<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('seller_transfers', function (Blueprint $table) {
            $table->unsignedBigInteger('settlement_amount_minor')->nullable()->after('currency');
            $table->char('settlement_currency', 3)->nullable()->after('settlement_amount_minor');
        });
    }

    public function down(): void
    {
        Schema::table('seller_transfers', function (Blueprint $table) {
            $table->dropColumn(['settlement_amount_minor', 'settlement_currency']);
        });
    }
};
