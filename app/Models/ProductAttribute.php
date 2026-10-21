<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $key
 * @property string $value
 * @property int $product_id
 * @property-read \App\Models\Product $product
 *
 * @mixin \Eloquent
 */
class ProductAttribute extends Model
{
    public $table = 'product_attributes';

    public $fillable = ['key', 'value', 'numeric_value', 'unit', 'product_id'];

    public $timestamps = false;

    protected $casts = [
        'numeric_value' => 'decimal:4',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
