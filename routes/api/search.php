<?php

use App\Http\Controllers\ReviewController;
use App\Http\Controllers\SearchController;

Route::prefix('v2/search/')->middleware(['auth:sanctum', 'sanctum.auth.json'])->group(function () {
    Route::post('keywords', [SearchController::class, 'keywords']);
    Route::post('/', [SearchController::class, 'search']);
});
