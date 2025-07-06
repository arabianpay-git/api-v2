<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\SuppliersController;
Route::get('/suppliers/all', [SuppliersController::class, 'getSuppliers']);
Route::get('/suppliers/details/{id}', [SuppliersController::class, 'getSupplierDetails']);
Route::get('/suppliers/products/{id}', [SuppliersController::class, 'getSupplierProducts']);
