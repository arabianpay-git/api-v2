<?php

use App\Http\Controllers\FollowController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\WishlistController;

Route::prefix('v2/followed/')->middleware(['auth:sanctum', 'sanctum.auth.json'])->group(function () {
        Route::get('get', [FollowController::class, 'get']);
        Route::post('add', [FollowController::class, 'add']);
        Route::post('remove', [FollowController::class, 'remove']);
});
