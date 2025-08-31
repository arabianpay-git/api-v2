<?php

use App\Helpers\EncryptionService;
use App\Http\Controllers\HomeController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/test', [HomeController::class, 'test'])->name('test');

Route::get('/enc', function () {
    return view('encryption');
});

Route::get('/enc2', function () {
    return view('encryption2');
});

Route::post('/encryption-tool', [HomeController::class, 'process'])->name('encryption.tool.process');
Route::post('/encryption-tool2', [HomeController::class, 'process2'])->name('encryption.tool.process2');
Route::get('/upload-old', [HomeController::class, 'upload'])->name('upload.old');


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
