<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $code
 * @property float $percentage
 * @property string $expire_date
 * @property int $max_usage
 * @property int $used_count
 * @property int $seller_id
 * @property int $is_active
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 *
 * @method static \Illuminate\Database\Eloquent\Builder|Coupon newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Coupon newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Coupon query()
 * @method static \Illuminate\Database\Eloquent\Builder|Coupon whereCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Coupon whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Coupon whereExpireDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Coupon whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Coupon whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Coupon whereMaxUsage($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Coupon whereSellerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Coupon wherePercentage($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Coupon whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Coupon whereUsedCount($value)
 *
 * @mixin \Eloquent
 */
class Coupon extends Model
{
    public $table = 'coupons';

    protected $fillable = [
        'code',
        'percentage',
        'expire_date',
        'max_usage',
        'used_count',
        'seller_id',
        'is_active',
    ];

    protected $casts = [
        'percentage' => 'float',
        'expire_date' => 'date:Y-m-d',
        'is_active' => 'boolean',
    ];

    public function isExpired(): bool
    {
        return $this->expire_date->lt(today());
    }

    public function isExhausted(): bool
    {
        return $this->used_count >= $this->max_usage;
    }

    public function is_invalid()
    {
        return $this->isExpired() || ! $this->is_active || $this->isExhausted();
    }
}
