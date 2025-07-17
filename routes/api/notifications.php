<?php

use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\SearchController;

Route::prefix('v2/notification/')->middleware(['auth:sanctum', 'sanctum.auth.json'])->group(function () {
    Route::get('get', [NotificationController::class, 'getNotifications']);
    Route::post('update-read', [NotificationController::class, 'updateRead']);
});
