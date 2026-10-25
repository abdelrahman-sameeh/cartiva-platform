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
        Schema::create('product_attributes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->string('key', 50);
            $table->string('value', 255);
            $table->decimal('numeric_value', 15, 4)->nullable();
            $table->string('unit', 30)->nullable();

            $table->unique(['product_id', 'key', 'value'], 'product_attributes_product_key_value_unique');
            $table->index(['key', 'value', 'product_id'], 'product_attributes_key_value_product_index');
            $table->index(
                ['key', 'unit', 'numeric_value', 'product_id'],
                'product_attributes_numeric_filter_index'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_attributes');
    }
};
