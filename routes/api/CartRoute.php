<?php

use App\Http\Controllers\Api\CartApiController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'roles:user'])->group(function () {
    Route::get('cart', [CartApiController::class, 'index']);
    Route::post('cart/items', [CartApiController::class, 'addItems']);
    Route::put('cart/items/{item}', [CartApiController::class, 'updateItem']);
    Route::delete('cart/items/{item}', [CartApiController::class, 'removeItem']);
    Route::delete('cart', [CartApiController::class, 'clear']);
    Route::post('cart/coupon', [CartApiController::class, 'applyCoupon']);
    Route::delete('cart/coupon', [CartApiController::class, 'removeCoupon']);
    Route::post('cart/validate', [CartApiController::class, 'validateCart']);
});
