<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SellerStripeAccount extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_ACTION_REQUIRED = 'action_required';

    public const STATUS_READY = 'ready';

    public const STATUS_RESTRICTED = 'restricted';

    protected $fillable = [
        'seller_id',
        'stripe_account_id',
        'onboarding_status',
        'payouts_status',
        'transfers_status',
        'requirements_due',
        'disabled_reason',
        'last_synced_at',
    ];

    protected $casts = [
        'requirements_due' => 'array',
        'last_synced_at' => 'datetime',
    ];

    public function isReady(): bool
    {
        return $this->onboarding_status === self::STATUS_READY
            && $this->transfers_status === 'active';
    }

    public function hasOutstandingRequirements(): bool
    {
        return ! empty($this->requirements_due);
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'seller_id');
    }
}
