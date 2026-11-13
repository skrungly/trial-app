<?php

use App\Http\Controllers\Factory\OperativeController;
use App\Http\Controllers\Factory\PickListController;
use App\Http\Controllers\Factory\PigeonholeController;
use App\Http\Controllers\Factory\UnitController;
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
    Route::get('operatives', [OperativeController::class, 'index'])->name('operatives.index');

    Route::apiResource('pick-lists', PickListController::class)->only(['index', 'store']);

    Route::get('pigeonholes', [PigeonholeController::class, 'index'])->name('pigeonholes.index');

    // Units are created with their order, so here they can only be listed and have their status changed.
    Route::get('units', [UnitController::class, 'index'])->name('units.index');
    Route::patch('units/{unit}', [UnitController::class, 'update'])->name('units.update');
});
