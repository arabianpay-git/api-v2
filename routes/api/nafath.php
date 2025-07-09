<?php


use App\Http\Controllers\SuppliersController;
Route::prefix('v2')->group(function () {
    Route::get('/nafath/send-request', [SuppliersController::class, 'getSuppliers']);
    Route::get('/nafath/check-status', [SuppliersController::class, 'getSuppliers']);
});