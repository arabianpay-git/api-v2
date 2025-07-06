<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\OrdersController;
Route::middleware('auth:sanctum')->post('/orders/store', [OrdersController::class, 'sendOrder']);
