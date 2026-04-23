<?php


use App\Modules\Core\Controllers\ImageController;
use App\Modules\Core\Controllers\DashboardController;
use App\Modules\Core\Controllers\SyncMonitorController;
use App\Modules\Core\Controllers\WebhookLogsController;
use App\Modules\Core\Controllers\WebhookSubscriptionsController;
use Illuminate\Support\Facades\Route;


Route::post('/convert-image', [ImageController::class, 'convert']);
Route::get('/download-image/{fileName}', [ImageController::class, 'download']);


Route::prefix('admin')->group(function () {

    Route::middleware(['auth:sanctum', 'role:partner|admin'])->group(function () {
        Route::post('sync-monitor/{type}', [SyncMonitorController::class, 'index']);
        Route::post('allWebhookLogs', [WebhookLogsController::class, 'index']);
        Route::post('allWebhookSubscriptions', [WebhookSubscriptionsController::class, 'index']);
    });

    Route::middleware(['auth:sanctum', 'role:admin'])->group(function () {

        Route::controller(DashboardController::class)->group(function () {
            Route::get('statistics', 'statistics')->name('statistics');
            Route::get('quick-stats', 'quickStats')->name('quick-stats');

            Route::get('/icon-statistics', 'iconStatistics');
            Route::get('/icon-quick-stats', 'quickIconStats');
        });
    });
});
