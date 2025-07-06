<?php


use App\Http\Controllers\ProductsController;
Route::get('/products/all', [ProductsController::class, 'index']);
Route::get('/product-details/{id}', [ProductsController::class, 'getProductDetails']);
Route::get('/products/filter', [ProductsController::class, 'getProductFilters']);
