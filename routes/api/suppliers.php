<?php


use App\Http\Controllers\SuppliersController;
Route::prefix('v2')->middleware(['auth:sanctum', 'sanctum.auth.json'])->group(function () {
    Route::get('/suppliers/all', [SuppliersController::class, 'getSuppliers']);
    Route::get('/suppliers/details/{id}', [SuppliersController::class, 'getSupplierDetails']);
    Route::get('/suppliers/products/{id}', [SuppliersController::class, 'getSupplierProducts']);
});