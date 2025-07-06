<?php

use App\Http\Controllers\PaymentsController;
Route::middleware('auth:sanctum')->post('/payments/pay', [PaymentsController::class, 'createPayment']);
Route::get('/payment/clickpay/callback', [PaymentsController::class, 'handleClickPayCallback'])
    ->name('clickpay.callback');