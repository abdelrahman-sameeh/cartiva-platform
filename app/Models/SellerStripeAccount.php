<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SellerStripeAccount extends Model
{
    protected $fillable = [
        'seller_id',
        'stripe_account_id',
        'card_payments_status',
        'payouts_status',
        'transfers_status',
        'requirements',
        'last_synced_at',
    ];

    protected $casts = [
        'requirements' => 'array',
        'last_synced_at' => 'datetime',
    ];

    public function isReady(): bool
    {
        return $this->card_payments_status === 'active'
            && $this->payouts_status === 'active'
            && $this->transfers_status === 'active';
    }

    public function hasOutstandingRequirements(): bool
    {
        return ! empty($this->requirements);
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'seller_id');
    }
}
