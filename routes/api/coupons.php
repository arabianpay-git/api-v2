<?php

use App\Http\Controllers\CouponController;

Route::prefix('v2/coupons/')->middleware(['auth:sanctum', 'sanctum.auth.json'])->group(function () {
    Route::get('list', [CouponController::class, 'list']);
    Route::post('apply', [CouponController::class, 'apply']);
    Route::post('remove', [CouponController::class, 'remove']);
    Route::post('products', [CouponController::class, 'products']);
});
