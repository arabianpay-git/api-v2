<?php

use App\Http\Controllers\AuthController;
Route::prefix('v2/auth')->group(function () {
    Route::post('/request-otp', [AuthController::class, 'requestOtp']);
    Route::post('/verify-otp', [AuthController::class, 'verifyOtp']);
});

Route::prefix('v2/auth/register')->group(function () {
    Route::post('/request-otp', [AuthController::class, 'requestOtpRegister']);
    Route::post('/verify-otp', [AuthController::class, 'verifyRegistration']);
    Route::post('/verify-id-with-nafath', [AuthController::class, 'verifyWithNafath']);
    Route::post('/check-nafath-status', [AuthController::class, 'checkNafathStatus']);
    Route::post('/complete-register-customer', [AuthController::class, 'completeRegisterCustomer']);
});

Route::middleware(['auth:sanctum', 'sanctum.auth.json'])->post('v2/auth/logout', [AuthController::class, 'logout']);
