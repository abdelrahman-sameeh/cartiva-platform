<?php

use App\Http\Controllers\Web\OrderCheckoutController;
use App\Http\Controllers\Web\OrderWebController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'roles:user'])->group(function () {
    Route::get('/orders', [OrderWebController::class, 'index'])->name('orders.index');
    Route::get('/orders/create', [OrderWebController::class, 'create'])->name('orders.create');
    Route::post('/orders', [OrderWebController::class, 'store'])->name('orders.store');
    Route::post('/orders/{order}/payment', [OrderCheckoutController::class, 'store'])
        ->name('orders.payment.store');
    Route::get('/orders/{order}/payment/return', [OrderCheckoutController::class, 'returned'])
        ->name('orders.payment.return');
    Route::get('/orders/{order}', [OrderWebController::class, 'show'])->name('orders.show');
});

Route::middleware(['auth', 'roles:seller'])
    ->prefix('seller')
    ->name('seller.')
    ->group(function () {
        Route::get('/orders', [OrderWebController::class, 'sellerIndex'])->name('orders.index');
        Route::get('/orders/{subOrder}', [OrderWebController::class, 'sellerShow'])->name('orders.show');
        Route::put('/orders/{subOrder}/status', [OrderWebController::class, 'updateSubOrderStatus'])
            ->name('orders.status.update');
    });
