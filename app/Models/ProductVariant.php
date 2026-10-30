<?php

namespace App\Models;

use App\Support\ProductVariantOptions;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductVariant extends Model
{
    protected $fillable = [
        'product_id',
        'sku',
        'price',
        'stock',
        'option_signature',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'stock' => 'integer',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function options(): HasMany
    {
        return $this->hasMany(ProductVariantOption::class);
    }

    /** @return array<string, string> */
    public function getOptionValuesAttribute(): array
    {
        return $this->options->pluck('value', 'key')->all();
    }

    public function getOptionLabelAttribute(): string
    {
        return ProductVariantOptions::label($this->option_values);
    }

    public function priceAfterDiscount(?Product $product = null): float
    {
        $product ??= $this->product;
        $basePrice = (float) ($this->price ?? $product->price);

        return max(0, round($basePrice - (float) $product->discount_amount, 2));
    }
}
