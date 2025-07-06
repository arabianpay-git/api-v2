<?php


use App\Http\Controllers\CategoriesController;
use App\Http\Controllers\ProductsController;
Route::get('/categories/all', [CategoriesController::class, 'getCategories']);

