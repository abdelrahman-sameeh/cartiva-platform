<?php

use App\Http\Controllers\Api\AddressApiController;
use Illuminate\Support\Facades\Route;

Route::apiResource('addresses', AddressApiController::class)
    ->middleware('auth:sanctum')
    ->names('api.addresses');
