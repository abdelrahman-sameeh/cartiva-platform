<?php

namespace Database\Seeders\TestData;

use App\Models\Cart\Cart;
use App\Models\Cart\CartCoupon;
use App\Models\Coupon;
use App\Models\User;
use Illuminate\Database\Seeder;

class CartCouponSeeder extends Seeder
{
    public function run(): void
    {
        $buyer = User::query()->where('email', 'buyer1@gmail.com')->firstOrFail();
        $cart = Cart::query()->where('user_id', $buyer->id)->firstOrFail();
        $coupon = Coupon::query()->where('code', 'خصم_الموضة_١٠')->firstOrFail();

        CartCoupon::create([
            'cart_id' => $cart->id,
            'coupon_id' => $coupon->id,
        ]);
    }
}
