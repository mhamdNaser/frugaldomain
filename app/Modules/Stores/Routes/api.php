<?php

use App\Modules\Stores\Controllers\StoreController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin')->group(function () {

    Route::middleware(['auth:sanctum', 'role:admin'])->group(function () {

        Route::controller(StoreController::class)->group(function () {
            Route::post('stores', 'index')->name('store');
            Route::post('store', 'store')->name('create-store');
            Route::put('store/{id}', 'update')->name('update-store');
            Route::patch('store/{id}/status', 'changStatus')->name('changestatus-user');
            Route::delete('store/{id}', 'destroy')->name('delete-store');
        });
    });
});

