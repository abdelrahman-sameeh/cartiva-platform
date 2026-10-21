<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->string('size', 30)->nullable()->change();
            $table->string('color', 50)->nullable()->change();
        });
    }

    public function down(): void
    {
        DB::table('order_items')->whereNull('size')->update(['size' => '']);
        DB::table('order_items')->whereNull('color')->update(['color' => '']);

        Schema::table('order_items', function (Blueprint $table) {
            $table->string('size', 30)->nullable(false)->change();
            $table->string('color', 50)->nullable(false)->change();
        });
    }
};
