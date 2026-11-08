<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('coupons', function (Blueprint $table) {
            $table->id();
            $table->string("code", 50);
            $table->float("percentage");
            $table->date("expire_date");
            $table->integer("max_usage");
            $table->integer("used_count")->default(0);
            $table->foreignId('seller_id')->constrained('users')->cascadeOnDelete();
            $table->boolean('is_active')->default(true);
            $table->unique(['code', 'seller_id']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('coupons');
    }
};
