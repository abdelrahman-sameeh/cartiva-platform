<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;
use Str;

/**
 * @property int $id
 * @property string $title
 * @property string $slug
 * @property string $description
 * @property string|null $cover_image
 * @property string $price
 * @property-read int $stock
 * @property int $seller_id
 * @property int $is_active
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property int $sold_count
 * @property string $rating_avg
 * @property int $rating_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ProductAttribute> $attributes
 * @property-read int|null $attributes_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ProductPicture> $pictures
 * @property-read int|null $pictures_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ProductVariant> $variants
 * @property-read int|null $variants_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\SubCategory> $sub_categories
 * @property-read int|null $sub_categories_count
 */
class Product extends Model
{
    public $table = 'products';

    protected $appends = ['cover_image_url', 'price_after_discount', 'stock'];

    protected $fillable = [
        'title',
        'description',
        'price',
        'discount_amount',
        'seller_id',
        'cover_image',
        'is_active',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'rating_avg' => 'decimal:1',
        'rating_count' => 'integer',
    ];

    public function getCoverImageUrlAttribute()
    {
        return url($this->cover_image);
    }

    public function getPriceAfterDiscountAttribute(): float
    {
        $price = (float) $this->price;
        $discount = (float) ($this->discount_amount ?? 0);

        return max(0, round($price - $discount, 2));
    }

    public function getStockAttribute(): int
    {
        if (array_key_exists('variants_sum_stock', $this->attributes)) {
            return (int) $this->attributes['variants_sum_stock'];
        }

        if ($this->relationLoaded('variants')) {
            return (int) $this->variants->sum('stock');
        }

        return (int) $this->variants()->sum('stock');
    }

    public static function booted()
    {
        static::creating(function ($product) {
            $product->slug = Str::slug($product->title);
        });

        static::updating(function ($product) {
            $product->slug = Str::slug($product->title);
        });

        static::deleting(function ($product) {
            if ($product->cover_image) {
                $path = str_replace('/storage/', '', $product->cover_image);
                Storage::disk('public')->delete($path);
            }
            foreach ($product->pictures as $pic) {
                $path = str_replace('/storage/', '', $pic->picture);
                Storage::disk('public')->delete($path);
            }
        });
    }

    public function pictures()
    {
        return $this->hasMany(ProductPicture::class, 'product_id');
    }

    public function attributes()
    {
        return $this->hasMany(ProductAttribute::class);
    }

    public function variants()
    {
        return $this->hasMany(ProductVariant::class);
    }

    public function sub_categories()
    {
        return $this->belongsToMany(SubCategory::class, ProductSubCategory::class, 'product_id', 'sub_category_id');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }
}
