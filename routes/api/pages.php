<?php


use App\Http\Controllers\PagesController;
Route::prefix('v2/pages')->group(function () {
    Route::get('/terms-conditions-client', [PagesController::class, 'getTermsConditions']);
    Route::get('/replacement-policy', [PagesController::class, 'getReplacementPolicy']);
    Route::get('/privacy-policy', [PagesController::class, 'getReplacementPolicy']);
    Route::get('/simah-policy', [PagesController::class, 'getReplacementPolicy']);
});