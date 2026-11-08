<?php

namespace App\Models\Order;

use App\Models\Address;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $user_id
 * @property string $subtotal
 * @property string $discount
 * @property string $total_price
 * @property string $phone
 * @property int|null $address_id
 * @property string $status
 * @property string $payment_status
 * @property string $payment_method
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read User $user
 * @property-read Address|null $address
 * @property-read \Illuminate\Database\Eloquent\Collection<int, SubOrder> $subOrders
 * @property-read int|null $sub_orders_count
 */
class Order extends Model
{
    public $table = 'orders';

    protected $fillable = [
        'user_id',
        'subtotal',
        'discount',
        'total_price',
        'phone',
        'address_id',
        'status',
        'payment_status',
        'payment_method',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'discount' => 'decimal:2',
        'total_price' => 'decimal:2',
    ];

    // ─── Relations ────────────────────────────────────────────────────────────

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function address()
    {
        return $this->belongsTo(Address::class);
    }

    public function subOrders()
    {
        return $this->hasMany(SubOrder::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }
}
