<?php

use App\Http\Controllers\Web\ReviewWebController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {
    Route::post('reviews', [ReviewWebController::class, 'store'])->name('reviews.store');
    Route::get('users/me/reviews', [ReviewWebController::class, 'specificUserReviews'])->name('reviews.mine');
    Route::patch('reviews/{review}', [ReviewWebController::class, 'update'])->name('reviews.update');
    Route::delete('reviews/{review}', [ReviewWebController::class, 'destroy'])->name('reviews.destroy');
});

Route::get('products/{product}/reviews', [ReviewWebController::class, 'productReviews'])
    ->name('products.reviews.index');
