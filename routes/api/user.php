<?php


use App\Http\Controllers\UsersController;
Route::prefix('v2/user')->middleware(['auth:sanctum', 'sanctum.auth.json'])->group(function () {
    Route::get('/payments/list', [UsersController::class, 'getPayments']);
    Route::get('/payments/details/{uuid}', [UsersController::class, 'getPaymentDetails']);
    Route::get('/payments/spent', [UsersController::class, 'getSpent']);

    Route::get('/cards/list', [UsersController::class, 'getCards']);
    

    Route::get('/profile', [UsersController::class, 'getProfile']);

    Route::get('/info', [UsersController::class, 'getInfo']);

    Route::post('/update-profile', [UsersController::class, 'updateProfile']);
});
