<?php

use App\Http\Controllers\OrdersController;
Route::prefix('v2/orders/')->middleware(['auth:sanctum', 'sanctum.auth.json'])->group(function () {
    Route::post('store', [OrdersController::class, 'sendOrder']);
    Route::get('get-orders', [OrdersController::class, 'getOrders']);
    Route::get('order-details/{id}', [OrdersController::class, 'getOrderDetails']);
    Route::get('get-address/{id}', [OrdersController::class, 'getAddress']);
});

Route::prefix('v2/orders/pending')->middleware(['auth:sanctum', 'sanctum.auth.json'])->group(function () {
    Route::get('get', [OrdersController::class, 'getPendingOrders']);
    Route::get('details', [OrdersController::class, 'getPendingOrderDetails']);
    Route::post('remove', [OrdersController::class, 'removePendingOrder']);
});
