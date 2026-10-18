<?php

use App\Http\Controllers\Web\CouponWebController;
use App\Http\Controllers\Web\ProductWebController;
use App\Http\Controllers\Web\StripeConnectController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'roles:seller'])
    ->prefix('seller')
    ->name('seller.')
    ->group(function () {
        Route::get('/products', [ProductWebController::class, 'index'])->name('products.index');
        Route::get('/products/create', [ProductWebController::class, 'create'])->name('products.create');
        Route::post('/products', [ProductWebController::class, 'store'])->name('products.store');
        Route::get('/products/{product}', [ProductWebController::class, 'show'])->name('products.show');
        Route::get('/products/{product}/edit', [ProductWebController::class, 'edit'])->name('products.edit');
        Route::put('/products/{product}', [ProductWebController::class, 'update'])->name('products.update');
        Route::delete('/products/{product}', [ProductWebController::class, 'destroy'])->name('products.destroy');

        Route::get('/coupons', [CouponWebController::class, 'index'])->name('coupons.index');
        Route::get('/coupons/create', [CouponWebController::class, 'create'])->name('coupons.create');
        Route::post('/coupons', [CouponWebController::class, 'store'])->name('coupons.store');
        Route::get('/coupons/{coupon}/edit', [CouponWebController::class, 'edit'])->name('coupons.edit');
        Route::put('/coupons/{coupon}', [CouponWebController::class, 'update'])->name('coupons.update');
        Route::delete('/coupons/{coupon}', [CouponWebController::class, 'destroy'])->name('coupons.destroy');

        Route::get('/stripe-connect', [StripeConnectController::class, 'show'])->name('stripe-connect.show');
        Route::post('/stripe-connect/onboard', [StripeConnectController::class, 'onboard'])->name('stripe-connect.onboard');
        Route::get('/stripe-connect/refresh', [StripeConnectController::class, 'refresh'])->name('stripe-connect.refresh');
        Route::get('/stripe-connect/return', [StripeConnectController::class, 'handleReturn'])->name('stripe-connect.return');
    });
