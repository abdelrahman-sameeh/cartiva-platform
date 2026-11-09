<?php

namespace App\Models;

use App\Models\Order\SubOrder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Shipment extends Model
{
    protected $fillable = [
        'sub_order_id',
        'provider',
        'tracking_number',
        'status',
        'cod_status',
        'cod_amount',
        'platform_fee',
        'shipping_fee',
        'collection_fee',
        'seller_net',
        'picked_up_at',
        'delivered_at',
        'cod_collected_at',
        'cod_remitted_at',
        'seller_paid_at',
    ];

    protected $casts = [
        'cod_amount' => 'decimal:2',
        'platform_fee' => 'decimal:2',
        'shipping_fee' => 'decimal:2',
        'collection_fee' => 'decimal:2',
        'seller_net' => 'decimal:2',
        'picked_up_at' => 'datetime',
        'delivered_at' => 'datetime',
        'cod_collected_at' => 'datetime',
        'cod_remitted_at' => 'datetime',
        'seller_paid_at' => 'datetime',
    ];

    public function subOrder(): BelongsTo
    {
        return $this->belongsTo(SubOrder::class);
    }
}
