<?php

namespace App\Models;

use App\Models\Order\Order;
use App\Models\Order\SubOrder;
use Illuminate\Database\Eloquent\Model;

class Refund extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_PROCESSING = 'processing';

    public const STATUS_SUCCEEDED = 'succeeded';

    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'order_id', 'sub_order_id', 'payment_id', 'provider_refund_id', 'idempotency_key',
        'amount_minor', 'currency', 'status', 'failure_reason', 'refunded_at',
    ];

    protected $casts = ['amount_minor' => 'integer', 'refunded_at' => 'datetime'];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function subOrder()
    {
        return $this->belongsTo(SubOrder::class);
    }

    public function payment()
    {
        return $this->belongsTo(Payment::class);
    }
}
