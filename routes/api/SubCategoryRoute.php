<?php

use App\Http\Controllers\Api\SubCategoryController;
use Illuminate\Support\Facades\Route;

Route::get('category/{category}/sub-categories', [SubCategoryController::class, 'sub_categories_by_category']);
Route::get('sub-category/{sub_category}', [SubCategoryController::class, 'find_one']);
Route::get('sub-category', [SubCategoryController::class, 'list']);

Route::group(['middleware' => ['auth:sanctum', 'roles:admin']], function () {
    Route::post('sub-category', [SubCategoryController::class, 'create']);
    Route::patch('sub-category/{id}', [SubCategoryController::class, 'update']);
    Route::delete('sub-category/{id}', [SubCategoryController::class, 'delete_one']);
});
