<?php

use App\Http\Controllers\AddressesController;
use App\Http\Controllers\CommonController;

Route::prefix('v2')->middleware(['auth:sanctum', 'sanctum.auth.json'])->group(function () {
    Route::get('/common/get-business-categories', [CommonController::class, 'getBusinessCategories']);
    Route::get('/common/get-countries', [CommonController::class, 'getCountries']);
    Route::get('/common/get-states-by-country/{country_id}', [CommonController::class, 'getStates']);
    Route::get('/common/get-cities-by-state/{state_id}', [CommonController::class, 'getCities']);
});
