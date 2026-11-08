<?php

use App\Http\Controllers\Api\ProductController;
use Illuminate\Support\Facades\Route;

Route::group(['middleware' => ['auth:sanctum', 'roles:seller']], function () {
    Route::post('products', [ProductController::class, 'create']);
    Route::delete('products/{id}', [ProductController::class, 'delete_one']);
    Route::get('products/{id}', [ProductController::class, 'find_one']);
    Route::patch('products/{id}', [ProductController::class, 'update']);
    // add and delete picture from product gallery
    Route::post('products/{id}/pictures', [ProductController::class, 'add_product_pictures']);
    Route::delete('product/picture/{id}', [ProductController::class, 'delete_product_picture']);
    Route::delete('product/pictures', [ProductController::class, 'delete_product_pictures']);
});

Route::get('products', [ProductController::class, 'find']);
