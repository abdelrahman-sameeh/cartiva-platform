<?php

use App\Http\Controllers\Api\OrderApiController;
use Illuminate\Support\Facades\Route;

// Customer
Route::middleware(['auth:sanctum', 'roles:user'])->group(function () {
    Route::get('orders', [OrderApiController::class, 'index']);
    Route::get('orders/{order}', [OrderApiController::class, 'show']);
    Route::post('orders', [OrderApiController::class, 'store']);
});

// Seller
Route::middleware(['auth:sanctum', 'roles:seller'])->group(function () {
    Route::get('seller/orders', [OrderApiController::class, 'sellerOrders']);
    Route::get('seller/sub-orders/{subOrder}', [OrderApiController::class, 'showSellerOrder']);
    Route::put('seller/sub-orders/{subOrder}/status', [OrderApiController::class, 'updateSubOrderStatus']);
});
