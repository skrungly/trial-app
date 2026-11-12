<?php

use App\Http\Controllers\ManufacturerController;
use App\Http\Controllers\MaterialController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProductController;
use Illuminate\Support\Facades\Route;

Route::apiResources([
    'manufacturers' => ManufacturerController::class,
    'materials' => MaterialController::class,
    'products' => ProductController::class,
]);

// Orders can't be changed once placed.
Route::apiResource('orders', OrderController::class)->only(['index', 'store', 'show']);
