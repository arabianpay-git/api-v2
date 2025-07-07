<?php


use App\Http\Controllers\ProductsController;
Route::prefix('v2')->middleware('auth:sanctum')->group(function () {
    Route::get('/products/all', [ProductsController::class, 'index']);
    Route::get('/product-details/{id}', [ProductsController::class, 'getProductDetails']);
    Route::get('/products/filter', [ProductsController::class, 'getProductFilters']);
});
