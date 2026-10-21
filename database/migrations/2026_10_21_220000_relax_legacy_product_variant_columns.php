<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_variants', function (Blueprint $table) {
            $table->dropUnique('product_variants_product_id_size_color_unique');
            $table->string('size', 30)->nullable()->change();
            $table->string('color', 50)->nullable()->change();
        });
    }

    public function down(): void
    {
        DB::table('product_variants')->whereNull('size')->update(['size' => '']);
        DB::table('product_variants')->whereNull('color')->update(['color' => '']);

        Schema::table('product_variants', function (Blueprint $table) {
            $table->string('size', 30)->nullable(false)->change();
            $table->string('color', 50)->nullable(false)->change();
            $table->unique(['product_id', 'size', 'color']);
        });
    }
};
