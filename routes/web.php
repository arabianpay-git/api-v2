<?php

use App\Helpers\EncryptionService;
use App\Http\Controllers\HomeController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/enc', function () {
    return view('encryption');
});

Route::post('/encryption-tool', [HomeController::class, 'process'])->name('encryption.tool.process');


Route::get('/encrypt/{text}', function ($text, EncryptionService $encryptionService) {
    return [
        'text' => $text,
        'encrypted' => $encryptionService->encrypt($text),
    ];
});

Route::get('/decrypt/{text}', function ($text, EncryptionService $encryptionService) {
    return [
        'text' => $text,
        'decrypt' => $encryptionService->decrypt($text),
    ];
});
