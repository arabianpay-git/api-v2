<?php 
use App\Http\Controllers\Payments\ClickpayController;

Route::prefix('v2/clickpay')->group(function () {
    // عامة (Webhook/IPN) — لا تضع عليها auth
    Route::post('/ipn',   [ClickpayController::class, 'ipn'])->name('clickpay.ipn');
    Route::get('/return', [ClickpayController::class, 'return'])->name('clickpay.return');

    // محمية للمستخدم (Sanctum مثلاً)
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/initiate-token', [ClickpayController::class, 'initiateToken']);
        Route::post('/charge',         [ClickpayController::class, 'chargeWithToken']);
    });
});
