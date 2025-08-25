<?php


use App\Http\Controllers\UsersController;
Route::prefix('v2/kyc')->middleware(['auth:sanctum', 'sanctum.auth.json'])->group(function () {
    Route::get('/get', [UsersController::class, 'getKyc']);
    Route::post('/set', [UsersController::class, 'setKyc']);
});
