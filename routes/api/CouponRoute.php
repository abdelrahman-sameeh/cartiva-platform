<?php

use App\Http\Controllers\Api\CouponApiController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'roles:seller'])->group(function () {
    Route::get('coupons', [CouponApiController::class, 'index']);
    Route::post('coupons', [CouponApiController::class, 'store']);
    Route::delete('coupons', [CouponApiController::class, 'destroyAll']);
    Route::get('coupons/{coupon}', [CouponApiController::class, 'show']);
    Route::put('coupons/{coupon}', [CouponApiController::class, 'update']);
    Route::delete('coupons/{coupon}', [CouponApiController::class, 'destroy']);
});
