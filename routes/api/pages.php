<?php


use App\Http\Controllers\PagesController;
Route::prefix('v2')->middleware(['auth:sanctum', 'sanctum.auth.json'])->group(function () {
    Route::get('/terms-conditions-client', [PagesController::class, 'getTermsConditions']);
    Route::get('/replacement-policy', [PagesController::class, 'getReplacementPolicy']);
});