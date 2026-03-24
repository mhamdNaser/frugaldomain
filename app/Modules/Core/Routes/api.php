<?php


use App\Modules\Core\Controllers\ImageController;
use App\Modules\Core\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;


Route::post('/convert-image', [ImageController::class, 'convert']);
Route::get('/download-image/{fileName}', [ImageController::class, 'download']);


Route::prefix('admin')->group(function () {

    Route::middleware(['auth:sanctum', 'role:admin'])->group(function () {

        Route::controller(DashboardController::class)->group(function () {
            Route::get('statistics', 'statistics')->name('statistics');
            Route::get('quick-stats', 'quickStats')->name('quick-stats');
        });
    });
});


