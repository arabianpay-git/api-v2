<?php

use App\Http\Controllers\AddressesController;

Route::prefix('v2/addresses/')->middleware(['sanctum.auth.json'])->group(function () {
    Route::get('get', [AddressesController::class, 'getAddresses']);
    Route::post('store', [AddressesController::class, 'store']);
    Route::post('update/{address_id}', [AddressesController::class, 'update']);
    Route::post('set-default', [AddressesController::class, 'setDefault']);
    Route::post('remove', [AddressesController::class, 'remove']);
});
