<?php

use App\Http\Controllers\AddressesController;

Route::prefix('v2')->middleware(['auth:sanctum', 'sanctum.auth.json'])->group(function () {
    Route::get('/addresses/get', [AddressesController::class, 'getAddresses']);
    Route::post('/addresses/store', [AddressesController::class, 'store']);
});
