<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\HomeController;

Route::prefix('v2')->middleware('auth:sanctum')->group(function () {
    Route::get('/home-page/all', [HomeController::class, 'index']);
});
