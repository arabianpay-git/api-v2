<?php


use App\Http\Controllers\CategoriesController;
use App\Http\Controllers\ProductsController;
Route::prefix('v2')->middleware('auth:sanctum')->group(function () {
    Route::get('/categories/all', [CategoriesController::class, 'getCategories']);
});

