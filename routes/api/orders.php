<?php

use App\Http\Controllers\OrdersController;
Route::prefix('v2')->middleware('auth:sanctum')->group(function () {
    Route::post('/orders/store', [OrdersController::class, 'sendOrder']);
});
