<?php

use App\Http\Controllers\AddressesController;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/addresses/get', [AddressesController::class, 'getAddresses']);
    Route::post('/addresses/store', [AddressesController::class, 'store'])->middleware('auth:sanctum');
});
