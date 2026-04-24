<?php

use App\Modules\Shopify\Controllers\ShopifyProductsSyncController;
use App\Modules\Shopify\Controllers\ShopifyDataSyncController;
use App\Modules\Shopify\Controllers\StoreConnectionController;
use App\Modules\Shopify\Controllers\ShopifyWebhookController;
use App\Modules\Shopify\Controllers\ShopifyWebhookSecretController;
use Illuminate\Support\Facades\Route;



Route::post('/shopify/webhooks', [ShopifyWebhookController::class, 'handle']);

Route::prefix('admin')->group(function () {


    Route::middleware(['auth:sanctum', 'role:partner'])->group(function () {
        Route::put('/shopify/webhook-secret', [ShopifyWebhookSecretController::class, 'update']);
        Route::post('/sync/products', [ShopifyProductsSyncController::class, 'syncProducts']);
        Route::post('/sync/files', [ShopifyDataSyncController::class, 'files']);
        Route::post('/sync/customers', [ShopifyDataSyncController::class, 'customers']);
        Route::post('/sync/orders', [ShopifyDataSyncController::class, 'orders']);
        Route::post('/sync/draft-orders', [ShopifyDataSyncController::class, 'draftOrders']);
        Route::post('/sync/fulfillments', [ShopifyDataSyncController::class, 'fulfillments']);
        Route::post('/sync/financials', [ShopifyDataSyncController::class, 'financials']);
        Route::post('/sync/discounts', [ShopifyDataSyncController::class, 'discounts']);
        Route::post('/sync/content', [ShopifyDataSyncController::class, 'content']);
        Route::post('/sync/shop-details', [ShopifyDataSyncController::class, 'shopDetails']);
        Route::post('/sync/store-installs', [ShopifyDataSyncController::class, 'storeInstalls']);
        Route::post('/sync/webhook-subscriptions', [ShopifyDataSyncController::class, 'webhookSubscriptions']);
        Route::post('/sync/webhook-logs', [ShopifyDataSyncController::class, 'webhookLogs']);
        Route::post('/sync/shipping', [ShopifyDataSyncController::class, 'shipping']);
        Route::post('/sync/returns-exchanges-reverse', [ShopifyDataSyncController::class, 'returnsExchangesReverse']);
        Route::post('/sync/order-risk-channel', [ShopifyDataSyncController::class, 'orderRiskChannel']);
        Route::post('/sync/order-duties', [ShopifyDataSyncController::class, 'orderDuties']);
        Route::post('/sync/inventory-states', [ShopifyDataSyncController::class, 'inventoryStates']);
        Route::post('/sync/customer-marketing-consent', [ShopifyDataSyncController::class, 'customerMarketingConsent']);
        Route::post('/sync/product-advanced-media', [ShopifyDataSyncController::class, 'productAdvancedMedia']);
        Route::post('/sync/markets-price-lists', [ShopifyDataSyncController::class, 'marketsPriceLists']);
        Route::post('/sync/metaobject-definitions', [ShopifyDataSyncController::class, 'metaobjectDefinitions']);
        Route::post('/sync/selling-plans', [ShopifyDataSyncController::class, 'sellingPlans']);
        Route::post('/sync/themes', [ShopifyDataSyncController::class, 'themes']);
        Route::post('/sync/commerce', [ShopifyDataSyncController::class, 'commerce']);
        Route::post('/sync/bootstrap', [ShopifyDataSyncController::class, 'bootstrap']);
    });

    Route::middleware(['auth:sanctum', 'role:partner|admin'])->group(function () {

        Route::controller(StoreConnectionController::class)->group(function () {
            Route::get('connection-status/{id}', 'status')->name('connection-status');
            Route::get('save-shopify-store-details/{id}', 'SaveShopifyStoreDetails')->name('save-shopify-store-details');
        });
    });
});
