<?php

use App\Modules\Inventory\Controllers\InventoryController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin')->group(function () {

    Route::middleware(['auth:sanctum', 'role:partner'])->group(function () {
        Route::controller(InventoryController::class)->group(function () {
            Route::post('allInventories', 'index');
            Route::post('inventories', 'store');
            Route::get('inventories/{id}', 'show');
            Route::put('inventories/{id}', 'update');
            Route::delete('inventories/{id}', 'destroy');
        });

    });
});
