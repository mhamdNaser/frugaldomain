<?php

use App\Modules\Stores\Controllers\StoreController;
use App\Modules\Stores\Controllers\StoreSettingController;
use App\Modules\Stores\Controllers\AccountsManageController;
use App\Modules\Stores\Controllers\StoreBrandingController;
use App\Modules\Stores\Controllers\MyStoreController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin')->group(function () {

    Route::middleware(['auth:sanctum', 'role:partner'])->group(function () {
        Route::get('accounts-manage', [AccountsManageController::class, 'show']);

        // The store owner's own store: connect, check and follow the import.
        Route::controller(MyStoreController::class)->prefix('my-store')->group(function () {
            Route::get('/', 'show');
            Route::get('sync-status', 'syncStatus');
            Route::middleware('throttle:20,1')->group(function () {
                Route::post('verify', 'verify');
                Route::post('test', 'test');
                Route::post('connect', 'connect');
            });
        });
    });

    Route::middleware(['auth:sanctum', 'role:admin'])->group(function () {

        Route::controller(StoreController::class)->group(function () {
            Route::post('stores', 'index')->name('store');
            Route::post('store', 'store')->name('create-store');
            Route::put('store/{id}', 'update')->name('update-store');
            Route::patch('store/{id}/status', 'changStatus')->name('changestatus-user');
            Route::delete('store/{id}', 'destroy')->name('delete-store');
        });

        Route::controller(StoreSettingController::class)->group(function () {
            Route::post('allStoreSettings', 'index');
        });

        Route::controller(StoreBrandingController::class)->group(function () {
            Route::post('allStoreBrandings', 'index');
        });
    });
});
