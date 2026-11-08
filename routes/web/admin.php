<?php

use App\Http\Controllers\Web\AdminUserRoleController;
use App\Http\Controllers\Web\CategoryWebController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'roles:admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('/users/roles', [AdminUserRoleController::class, 'index'])->name('users.roles.index');
        Route::put('/users/{user}/roles', [AdminUserRoleController::class, 'update'])->name('users.roles.update');

        Route::get('/categories', [CategoryWebController::class, 'index'])->name('categories.index');
        Route::post('/categories', [CategoryWebController::class, 'store'])->name('categories.store');
        Route::delete('/categories/{category}', [CategoryWebController::class, 'destroy'])->name('categories.destroy');
        Route::post('/sub-categories', [CategoryWebController::class, 'storeSubCategory'])->name('sub-categories.store');
        Route::delete('/sub-categories/{subCategory}', [CategoryWebController::class, 'destroySubCategory'])->name('sub-categories.destroy');
    });
