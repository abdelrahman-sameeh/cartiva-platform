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
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string("title", 50);
            $table->string('slug')->unique();
        });

        Schema::create('sub_category', function (Blueprint $table) {
            $table->id();
            $table->string("title", 50);
            $table->string('slug')->unique();
            $table->foreignId("category_id")->constrained("categories")->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sub_category');
        Schema::dropIfExists('categories');
    }
};
