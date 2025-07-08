<?php

use App\Http\Controllers\AuthController;
Route::prefix('v2/auth')->group(function () {
    Route::post('/request-otp', [AuthController::class, 'requestOtp']);
    Route::post('/verify-otp', [AuthController::class, 'verifyOtp']);
});

Route::prefix('v2/auth/register')->group(function () {
    Route::post('/request-otp', [AuthController::class, 'requestOtpRegister']);
    Route::post('/verify-otp', [AuthController::class, 'verifyRegistration']);
});