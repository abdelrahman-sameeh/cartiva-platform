<?php

use App\Http\Controllers\Api\CategoryController;
use Illuminate\Support\Facades\Route;

Route::get('categories', [CategoryController::class, 'list']);
Route::get('categories/{id}', [CategoryController::class, 'find_one']);

Route::group(['middleware' => ['auth:sanctum', 'roles:admin']], function () {
    Route::post('categories', [CategoryController::class, 'create']);
    Route::patch('categories/{id}', [CategoryController::class, 'update']);
    Route::delete('categories/{id}', [CategoryController::class, 'delete']);
});
