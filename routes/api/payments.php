<?php

use App\Http\Controllers\PaymentsController;
Route::prefix('v2')->middleware(['auth:sanctum', 'sanctum.auth.json'])->group(function () {
    Route::post('/payments/pay', [PaymentsController::class, 'createPayment']);
    Route::get('/payment/clickpay/callback', [PaymentsController::class, 'handleClickPayCallback'])
        ->name('clickpay.callback');
});