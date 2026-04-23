<?php

use App\Modules\Marketing\Controllers\DiscountCodeController;
use App\Modules\Marketing\Controllers\DiscountController;
use App\Modules\Marketing\Controllers\DiscountUsageController;
use App\Modules\Marketing\Controllers\ExchangeController;
use App\Modules\Marketing\Controllers\MarketController;
use App\Modules\Marketing\Controllers\SellingPlanController;
use App\Modules\Marketing\Controllers\SellingPlanGroupController;
use App\Modules\Marketing\Controllers\SellingPlanSubscriptionController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin')->group(function () {
    Route::middleware(['auth:sanctum', 'role:partner'])->group(function () {
        Route::controller(DiscountController::class)->group(function () {
            Route::post('allDiscounts', 'index');
            Route::get('discounts/{id}', 'show');
            Route::put('discounts/{id}', 'update');
        });

        Route::controller(DiscountCodeController::class)->group(function () {
            Route::post('allDiscountCodes', 'index');
            Route::get('discount-codes/{id}', 'show');
            Route::put('discount-codes/{id}', 'update');
        });

        Route::controller(DiscountUsageController::class)->group(function () {
            Route::post('allDiscountUsages', 'index');
            Route::get('discount-usages/{id}', 'show');
            Route::put('discount-usages/{id}', 'update');
        });

        Route::controller(ExchangeController::class)->group(function () {
            Route::post('allExchanges', 'index');
        });

        Route::controller(MarketController::class)->group(function () {
            Route::post('allMarkets', 'index');
        });

        Route::controller(SellingPlanGroupController::class)->group(function () {
            Route::post('allSellingPlanGroups', 'index');
        });

        Route::controller(SellingPlanController::class)->group(function () {
            Route::post('allSellingPlans', 'index');
        });

        Route::controller(SellingPlanSubscriptionController::class)->group(function () {
            Route::post('allSellingPlanSubscriptions', 'index');
        });
    });
});
