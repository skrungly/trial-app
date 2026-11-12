<?php

use App\Http\Controllers\ManufacturerController;
use App\Http\Controllers\MaterialController;
use App\Http\Controllers\ProductController;
use Illuminate\Support\Facades\Route;

Route::apiResources([
    'manufacturers' => ManufacturerController::class,
    'materials' => MaterialController::class,
    'products' => ProductController::class,
]);
