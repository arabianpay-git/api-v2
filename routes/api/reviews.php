<?php

use App\Http\Controllers\ReviewController;

Route::prefix('v2/reviews/')->middleware(['auth:sanctum', 'sanctum.auth.json'])->group(function () {
    Route::get('get', [ReviewController::class, 'getProductReviews']);
    Route::post('add', [ReviewController::class, 'addProductReview']);
    Route::get('suppliers-rating', [ReviewController::class, 'getSuppliersRating']);
});
