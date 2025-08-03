<?php


use App\Http\Controllers\ProductsController;
Route::prefix('v2')->middleware(['auth:sanctum', 'sanctum.auth.json'])->group(function () {
    Route::get('/products/all', [ProductsController::class, 'index']);
    Route::get('/product-details/{id}', [ProductsController::class, 'getProductDetails']);
    Route::get('/products/related/{id}', [ProductsController::class, 'getProductRelated']);
    Route::get('/products/filter', [ProductsController::class, 'getProductFilters']);
    Route::get('/reviews/get', [ProductsController::class, 'getProductReviews']);
    Route::get('/wishlists/get', [ProductsController::class, 'getProductWishlists']);
    Route::post('/products/check-variation', [ProductsController::class, 'checkVariation']);
});
