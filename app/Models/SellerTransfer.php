<?php

namespace App\Models;

use App\Models\Order\SubOrder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SellerTransfer extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_PROCESSING = 'processing';

    public const STATUS_SUCCEEDED = 'succeeded';

    public const STATUS_FAILED = 'failed';

    public const STATUS_BLOCKED = 'blocked';

    public const STATUS_SKIPPED = 'skipped';

    protected $fillable = [
        'sub_order_id',
        'payment_id',
        'seller_id',
        'stripe_account_id',
        'stripe_transfer_id',
        'idempotency_key',
        'amount_minor',
        'currency',
        'settlement_amount_minor',
        'settlement_currency',
        'stripe_fee_minor',
        'platform_fee_minor',
        'platform_fee_bps',
        'transfer_amount_minor',
        'status',
        'failure_reason',
        'attempted_at',
        'transferred_at',
    ];

    protected $casts = [
        'amount_minor' => 'integer',
        'settlement_amount_minor' => 'integer',
        'stripe_fee_minor' => 'integer',
        'platform_fee_minor' => 'integer',
        'platform_fee_bps' => 'integer',
        'transfer_amount_minor' => 'integer',
        'attempted_at' => 'datetime',
        'transferred_at' => 'datetime',
    ];

    public function subOrder(): BelongsTo
    {
        return $this->belongsTo(SubOrder::class);
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'seller_id');
    }
}
