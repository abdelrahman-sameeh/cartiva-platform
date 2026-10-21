<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StripeWebhookEvent extends Model
{
    protected $fillable = [
        'event_id',
        'type',
        'processed_at',
    ];

    protected $casts = [
        'processed_at' => 'datetime',
    ];

    public static function pending(string $eventId, string $type): ?self
    {
        static::query()->insertOrIgnore([
            'event_id' => $eventId,
            'type' => $type,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $event = static::query()->where('event_id', $eventId)->firstOrFail();

        return $event->processed_at ? null : $event;
    }

    public function markProcessed(): void
    {
        $this->update(['processed_at' => now()]);
    }
}
