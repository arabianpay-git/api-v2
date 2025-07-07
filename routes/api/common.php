<?php

use App\Http\Controllers\AddressesController;
use App\Http\Controllers\CommonController;
Route::middleware('auth:sanctum')->get('/common/get-business-categories', [CommonController::class, 'getBusinessCategories']);
Route::middleware('auth:sanctum')->get('/common/get-countries', [CommonController::class, 'getCountries']);
Route::middleware('auth:sanctum')->get('/common/get-states-by-country/{country_id}', [CommonController::class, 'getStates']);
Route::middleware('auth:sanctum')->get('/common/get-cities-by-state/{state_id}', [CommonController::class, 'getCities']);

