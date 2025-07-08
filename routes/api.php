<?php

use Illuminate\Support\Facades\Route;

Route::get('v2/test', function () {
    return response()->json(['message' => 'API is working']);
});

require __DIR__.'/api/auth.php';
require __DIR__.'/api/products.php';
require __DIR__.'/api/suppliers.php';
require __DIR__.'/api/brands.php';
require __DIR__.'/api/orders.php';
require __DIR__.'/api/cart.php';
require __DIR__.'/api/home.php';
require __DIR__.'/api/categories.php';
require __DIR__.'/api/addresses.php';
require __DIR__.'/api/payments.php';
require __DIR__.'/api/common.php';