<?php

namespace App\Models\Order;

use App\Models\SellerTransfer;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property int $id
 * @property int $order_id
 * @property int $seller_id
 * @property string $subtotal
 * @property string $discount
 * @property string $total_price
 * @property string $status
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read Order $order
 * @property-read User $seller
 * @property-read \Illuminate\Database\Eloquent\Collection<int, OrderItem> $items
 * @property-read int|null $items_count
 */
class SubOrder extends Model
{
    public $table = 'sub_orders';

    protected $fillable = [
        'order_id',
        'seller_id',
        'subtotal',
        'discount',
        'total_price',
        'status',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'discount' => 'decimal:2',
        'total_price' => 'decimal:2',
    ];

    // ─── Relations ────────────────────────────────────────────────────────────

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function seller()
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function sellerTransfer(): HasOne
    {
        return $this->hasOne(SellerTransfer::class);
    }
}
