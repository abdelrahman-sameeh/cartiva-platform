<?php

namespace App\Services;

use App\Models\Coupon;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class CouponService
{
    public function forSeller(User $seller): Builder
    {
        return Coupon::query()->where('seller_id', $seller->id);
    }

    public function create(User $seller, array $data): Coupon
    {
        return Coupon::create([
            ...$data,
            'seller_id' => $seller->id,
        ]);
    }

    public function update(User $seller, Coupon $coupon, array $data): Coupon
    {
        $coupon = $this->ownedBy($seller, $coupon);
        $coupon->update($data);

        return $coupon->refresh();
    }

    public function delete(User $seller, Coupon $coupon): void
    {
        $this->ownedBy($seller, $coupon)->delete();
    }

    public function deleteAll(User $seller): int
    {
        return $this->forSeller($seller)->delete();
    }

    public function ownedBy(User $seller, Coupon $coupon): Coupon
    {
        abort_unless($coupon->seller_id === $seller->id, 404);

        return $coupon;
    }
}
