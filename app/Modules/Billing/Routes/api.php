<?php

use App\Modules\Billing\Controllers\PaymentTransactionController;
use App\Modules\Billing\Controllers\PlanController;
use App\Modules\Billing\Controllers\RefundController;
use App\Modules\Billing\Controllers\RefundItemController;
use App\Modules\Billing\Controllers\SubscriptionController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin')->group(function () {
    Route::middleware(['auth:sanctum', 'role:partner|admin'])->group(function () {
        Route::controller(PaymentTransactionController::class)->group(function () {
            Route::post('allPaymentTransactions', 'index');
            Route::get('payment-transactions/{id}', 'show');
            Route::put('payment-transactions/{id}', 'update');
        });

        Route::controller(RefundController::class)->group(function () {
            Route::post('allRefunds', 'index');
            Route::get('refunds/{id}', 'show');
            Route::put('refunds/{id}', 'update');
        });

        Route::controller(RefundItemController::class)->group(function () {
            Route::post('allRefundItems', 'index');
            Route::get('refund-items/{id}', 'show');
            Route::put('refund-items/{id}', 'update');
        });

        Route::controller(PlanController::class)->group(function () {
            Route::post('allPlans', 'index');
            Route::get('plans/{id}', 'show');
            Route::put('plans/{id}', 'update');
            Route::patch('plans/{id}/status', 'changeStatus');
        });

        Route::controller(SubscriptionController::class)->group(function () {
            Route::post('allSubscriptions', 'index');
            Route::get('subscriptions/{id}', 'show');
            Route::put('subscriptions/{id}', 'update');
        });
    });
});
