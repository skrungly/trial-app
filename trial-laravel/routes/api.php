<?php

use App\Http\Controllers\Factory\PickListController;
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

// Internal endpoints for running the factory floor.
Route::prefix('factory')->name('factory.')->group(function () {
    Route::apiResource('pick-lists', PickListController::class)->only(['store']);
});
