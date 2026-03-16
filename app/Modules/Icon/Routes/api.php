<?php

use App\Modules\Icon\Controllers\IconDownloadCopyController;
use App\Modules\Icon\Controllers\IconController;
use App\Modules\Icon\Controllers\IconCategoriesController;
use Illuminate\Support\Facades\Route;


Route::post('allicons/WithoutPagination', [IconController::class, 'allWithoutPagination'])->name('WithoutPagination');
Route::get('icon-categories/WithoutPagination', [IconCategoriesController::class, 'allWithoutPagination'])->name('allWithoutPagination');

Route::controller(IconDownloadCopyController::class)->group(function () {
    Route::get('/download-icon/{fileName}', 'download');
    Route::get('/download-count/{fileName}', 'downloadCount');
    Route::get('/get-icon-svg/{fileName}', 'getIconCode');
    Route::get('/get-icon-jsx/{fileName}', 'getIconCodeJsx');
});

Route::prefix('admin')->group(function () {
    Route::middleware(['auth:sanctum', 'role:admin'])->group(function () {;

        Route::controller(IconController::class)->group(function () {
            Route::post('allicons', 'index');
            Route::post('icons', 'store');
            Route::put('icons/{id}', 'update');
            Route::delete('icon/{id}', 'destroy');
            Route::delete('icons', 'destroyArray');
            Route::patch('icons/{id}/status', 'changeStatus');
        });

        Route::controller(IconCategoriesController::class)->group(function () {
            Route::post('icon-categories/all', 'index');
            Route::post('icon-category', 'store');
            Route::put('icon-categories/{id}', 'update');
            Route::delete('icon-category/{id}', 'destroy');
            Route::delete('icon-categories', 'destroyArray');
            Route::patch('icon-categories/{id}/status', 'changeStatus');
        });
    });
});
