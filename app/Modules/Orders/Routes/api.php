<?php

use App\Modules\Orders\Controllers\CartController;
use App\Modules\Orders\Controllers\CartItemController;
use App\Modules\Orders\Controllers\DraftOrderController;
use App\Modules\Orders\Controllers\DraftOrderItemController;
use App\Modules\Orders\Controllers\OrderController;
use App\Modules\Orders\Controllers\OrderDutyController;
use App\Modules\Orders\Controllers\OrderItemController;
use App\Modules\Orders\Controllers\OrderItemDutyController;
use App\Modules\Orders\Controllers\OrderReturnController;
use App\Modules\Orders\Controllers\OrderReturnItemController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin')->group(function () {
    Route::middleware(['auth:sanctum', 'role:partner'])->group(function () {
        
        Route::controller(OrderController::class)->group(function () {
            Route::post('allOrders', 'index');
            Route::get('orders/{id}', 'show');
            Route::put('orders/{id}', 'update');
        });

        Route::controller(OrderItemController::class)->group(function () {
            Route::post('allOrderItems', 'index');
            Route::get('order-items/{id}', 'show');
            Route::put('order-items/{id}', 'update');
        });

        Route::controller(OrderDutyController::class)->group(function () {
            Route::post('allOrderDuties', 'index');
            Route::get('order-duties/{id}', 'show');
            Route::put('order-duties/{id}', 'update');
        });

        Route::controller(OrderItemDutyController::class)->group(function () {
            Route::post('allOrderItemDuties', 'index');
            Route::get('order-item-duties/{id}', 'show');
            Route::put('order-item-duties/{id}', 'update');
        });

        Route::controller(OrderReturnController::class)->group(function () {
            Route::post('allOrderReturns', 'index');
            Route::get('order-returns/{id}', 'show');
            Route::put('order-returns/{id}', 'update');
        });

        Route::controller(OrderReturnItemController::class)->group(function () {
            Route::post('allOrderReturnItems', 'index');
            Route::get('order-return-items/{id}', 'show');
            Route::put('order-return-items/{id}', 'update');
        });

        Route::controller(DraftOrderController::class)->group(function () {
            Route::post('allDraftOrders', 'index');
            Route::get('draft-orders/{id}', 'show');
            Route::put('draft-orders/{id}', 'update');
        });

        Route::controller(DraftOrderItemController::class)->group(function () {
            Route::post('allDraftOrderItems', 'index');
            Route::get('draft-order-items/{id}', 'show');
            Route::put('draft-order-items/{id}', 'update');
        });

        Route::controller(CartController::class)->group(function () {
            Route::post('allCarts', 'index');
            Route::get('carts/{id}', 'show');
            Route::put('carts/{id}', 'update');
        });

        Route::controller(CartItemController::class)->group(function () {
            Route::post('allCartItems', 'index');
            Route::get('cart-items/{id}', 'show');
            Route::put('cart-items/{id}', 'update');
        });
    });
});
