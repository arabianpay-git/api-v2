<?php

use App\Http\Controllers\OrdersController;
Route::prefix('v2')->middleware(['auth:sanctum', 'sanctum.auth.json'])->group(function () {
    Route::post('/orders/store', [OrdersController::class, 'sendOrder']);
});
