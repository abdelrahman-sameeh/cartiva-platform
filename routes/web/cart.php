<?php

use App\Http\Controllers\Web\CartWebController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'roles:user'])->group(function () {
    Route::get('/cart', [CartWebController::class, 'index'])->name('cart.index');
    Route::post('/cart/items', [CartWebController::class, 'storeItem'])->name('cart.items.store');
    Route::put('/cart/items/{item}', [CartWebController::class, 'updateItem'])->name('cart.items.update');
    Route::delete('/cart/items/{item}', [CartWebController::class, 'removeItem'])->name('cart.items.destroy');
    Route::delete('/cart', [CartWebController::class, 'clear'])->name('cart.destroy');
    Route::post('/cart/coupon', [CartWebController::class, 'applyCoupon'])->name('cart.coupons.store');
    Route::delete('/cart/coupons/{coupon}', [CartWebController::class, 'removeCoupon'])->name('cart.coupons.destroy');
});
