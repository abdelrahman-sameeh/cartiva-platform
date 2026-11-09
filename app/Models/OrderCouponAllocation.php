<?php

namespace App\Models;

use App\Models\Order\Order;
use App\Models\Order\SubOrder;
use Illuminate\Database\Eloquent\Model;

class OrderCouponAllocation extends Model
{
    protected $fillable = ['order_id', 'sub_order_id', 'coupon_id', 'discount', 'released_at'];

    protected $casts = ['discount' => 'decimal:2', 'released_at' => 'datetime'];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function subOrder()
    {
        return $this->belongsTo(SubOrder::class);
    }

    public function coupon()
    {
        return $this->belongsTo(Coupon::class);
    }
}
