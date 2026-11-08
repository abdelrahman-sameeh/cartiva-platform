<?php

use App\Http\Controllers\Web\StorefrontController;
use Illuminate\Support\Facades\Route;

Route::get('/', [StorefrontController::class, 'index'])->name('home');
Route::get('/search', [StorefrontController::class, 'search'])->name('products.search');
Route::get('/products/{product}', [StorefrontController::class, 'show'])->name('products.show');
Route::get('/stores', [StorefrontController::class, 'stores'])->name('stores.index');

Route::prefix('stores/{seller}')->name('stores.')->group(function () {
    Route::get('/', [StorefrontController::class, 'sellerStore'])->name('show');
    Route::get('/search', [StorefrontController::class, 'sellerSearch'])->name('search');
    Route::get('/products/{product}', [StorefrontController::class, 'sellerProduct'])->name('products.show');
});
