<?php

use App\Modules\MobileApp\Controllers\MobileStorefrontController;
use Illuminate\Support\Facades\Route;

Route::prefix('mobile')->group(function () {
    Route::controller(MobileStorefrontController::class)->group(function () {
        Route::get('bootstrap', 'bootstrap');
        Route::get('navigation', 'navigation');

        Route::get('products', 'products');
        Route::get('products/{id}', 'productDetails');

        Route::get('collections', 'collections');
        Route::get('collections/{id}', 'collectionDetails');

        Route::get('pages', 'pages');
        Route::get('pages/{id}', 'pageDetails');

        Route::get('blogs', 'blogs');
        Route::get('blogs/{blogId}/articles', 'blogArticles');

        Route::get('search', 'search');
        Route::get('payment-methods', 'paymentMethods');

        Route::post('checkout/quote', 'checkoutQuote');
        Route::post('checkout/place-draft-order', 'placeDraftOrder');
    });
});
