<?php

namespace App\Models\Order;

use App\Models\Product;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $sub_order_id
 * @property int|null $product_id
 * @property string $title
 * @property string $slug
 * @property string $description
 * @property string|null $cover_image
 * @property string $price_at_purchase
 * @property string $size
 * @property string $color
 * @property int $quantity
 * @property \Illuminate\Support\Carbon|null $created_at_snapshot
 * @property-read SubOrder $subOrder
 * @property-read Product|null $product
 * @property-read \Illuminate\Database\Eloquent\Collection<int, OrderItemPicture> $pictures
 * @property-read int|null $pictures_count
 */
class OrderItem extends Model
{
    public $table = 'order_items';

    // ليس له timestamps خاصة به
    public $timestamps = false;

    protected $fillable = [
        'sub_order_id',
        'product_id',
        'title',
        'slug',
        'description',
        'cover_image',
        'price_at_purchase',
        'variant_sku',
        'variant_options',
        'size',
        'color',
        'quantity',
        'created_at_snapshot',
    ];

    protected $casts = [
        'price_at_purchase' => 'decimal:2',
        'variant_options' => 'array',
        'created_at_snapshot' => 'datetime',
    ];

    // ─── Relations ────────────────────────────────────────────────────────────

    public function subOrder()
    {
        return $this->belongsTo(SubOrder::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function pictures()
    {
        return $this->hasMany(OrderItemPicture::class);
    }
}
