<?php

use App\Http\Controllers\Web\AddressWebController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {
    Route::resource('addresses', AddressWebController::class)->except('show');
});
