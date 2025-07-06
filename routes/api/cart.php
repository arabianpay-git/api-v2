<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CartsController;
use App\Http\Controllers\HomeController;
Route::middleware('auth:sanctum')->post('/carts/set', [CartsController::class, 'setCart']);
Route::middleware('auth:sanctum')->get('/carts/get', [CartsController::class, 'getCart']);

