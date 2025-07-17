<?php

use App\Http\Controllers\ReviewController;
use App\Http\Controllers\WishlistController;

Route::prefix('v2/wishlists/')->middleware(['auth:sanctum', 'sanctum.auth.json'])->group(function () {
        Route::get('get', [WishlistController::class, 'get']);
        Route::post('add', [WishlistController::class, 'add']);
        Route::post('remove', [WishlistController::class, 'remove']);
});
