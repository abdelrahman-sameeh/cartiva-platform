<?php

namespace App\Models\Order;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $order_item_id
 * @property string $image_path
 *
 * @property-read OrderItem $orderItem
 */
class OrderItemPicture extends Model
{
    public $table = 'order_item_pictures';

    public $timestamps = false;

    protected $fillable = [
        'order_item_id',
        'image_path',
    ];

    // ─── Relations ────────────────────────────────────────────────────────────

    public function orderItem()
    {
        return $this->belongsTo(OrderItem::class);
    }
}
