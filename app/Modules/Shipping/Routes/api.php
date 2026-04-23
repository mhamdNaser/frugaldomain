<?php

use App\Modules\Shipping\Controllers\ShippingZoneController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin')->group(function () {
    Route::middleware(['auth:sanctum', 'role:partner'])->group(function () {
        Route::controller(ShippingZoneController::class)->group(function () {
            Route::post('allShippingZones', 'index');
            Route::get('shipping-zones/{id}', 'show');
            Route::put('shipping-zones/{id}', 'update');
        });
    });
});
