<?php

use App\Http\Controllers\Web\ReviewWebController;

Route::middleware(['auth'])->post('reviews', [ReviewWebController::class, 'store'])->name('reviews.store');


// GET /api/products/{product}/reviews
// POST	/api/products/{product}/reviews
// PATCH	/api/reviews/{review}
// DELETE	/api/reviews/{review}
// GET	/api/users/me/reviews