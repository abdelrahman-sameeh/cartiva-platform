<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SellerStripeAccount extends Model
{
    protected $fillable = [
        'seller_id',
        'stripe_account_id',
        'details_submitted',
        'charges_enabled',
        'payouts_enabled',
        'disabled_reason',
        'requirements_currently_due',
        'last_synced_at',
    ];

    protected $casts = [
        'details_submitted' => 'boolean',
        'charges_enabled' => 'boolean',
        'payouts_enabled' => 'boolean',
        'requirements_currently_due' => 'array',
        'last_synced_at' => 'datetime',
    ];

    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'seller_id');
    }
}
