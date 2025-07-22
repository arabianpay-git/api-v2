<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CartsController;
use App\Http\Controllers\HomeController;
use function Laravel\Prompts\confirm;

Route::prefix('v2')->middleware(['auth:sanctum', 'sanctum.auth.json'])->group(function () {
    Route::post('/carts/set', [CartsController::class, 'setCart']);
    Route::get('/carts/get', [CartsController::class, 'getCart']);
    Route::get('/carts/get-details', [CartsController::class, 'getCartDetails']);
    Route::post('/carts/post-checkout-details', [CartsController::class, 'checkout']);
    Route::post('/carts/get-checkout-details', [CartsController::class, 'getCheckout']);
    Route::post('/carts/get-checkout-details', [CartsController::class, 'getCheckout']);
    Route::post('/carts/resend-otp', [CartsController::class, 'resendOtp']);
    Route::post('/carts/confirm-otp', [CartsController::class, 'confirmOtp']);
    Route::post('/carts/send-order', [CartsController::class, 'sendOrder']);
    Route::post('/carts/create-sanad-nafith', [CartsController::class, 'createSanadNafith']);
});

