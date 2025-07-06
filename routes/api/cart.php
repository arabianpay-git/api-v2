<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\HomeController;
Route::get('/home-page/all', [HomeController::class, 'index']);
