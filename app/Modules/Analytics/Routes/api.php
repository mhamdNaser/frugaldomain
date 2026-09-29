<?php

use App\Modules\Analytics\Controllers\AnalyticsController;
use App\Modules\Analytics\Controllers\TrackController;
use Illuminate\Support\Facades\Route;

// Public beacon (optional Sanctum auth is resolved inside the controller).
Route::post('analytics/track', [TrackController::class, 'track'])
    ->middleware('throttle:60,1')
    ->name('analytics.track');

Route::prefix('admin')->middleware(['auth:sanctum', 'role:admin'])->group(function () {
    Route::controller(AnalyticsController::class)->prefix('analytics')->group(function () {
        Route::get('overview', 'overview')->name('admin.analytics.overview');
        Route::get('timeseries', 'timeseries')->name('admin.analytics.timeseries');
        Route::get('countries', 'countries')->name('admin.analytics.countries');
        Route::get('visitors', 'visitors')->name('admin.analytics.visitors');
        Route::get('pages', 'pages')->name('admin.analytics.pages');
    });
});
