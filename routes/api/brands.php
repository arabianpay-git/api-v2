<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BrandsController;
use App\Http\Controllers\HomeController;

Route::get('/brands/all', [BrandsController::class, 'getBrands']);
