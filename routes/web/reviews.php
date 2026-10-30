<?php

use App\Http\Controllers\Web\ReviewWebController;

Route::middleware(['auth'])->post('reviews', [ReviewWebController::class, 'store'])->name('reviews.store');
