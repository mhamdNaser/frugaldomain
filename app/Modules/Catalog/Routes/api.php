<?php

use App\Modules\Catalog\Controllers\References\CategoriesController;
use App\Modules\Catalog\Controllers\References\CollectionsController;
use App\Modules\Catalog\Controllers\References\OptionsController;
use App\Modules\Catalog\Controllers\References\ProductTypesController;
use App\Modules\Catalog\Controllers\References\TagsController;
use App\Modules\Catalog\Controllers\References\VendorsController;
use App\Modules\Catalog\Controllers\ProductController;
use App\Modules\Catalog\Controllers\ProductsDashboardController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin')->group(function () {

    Route::middleware(['auth:sanctum', 'role:partner'])->group(function () {

        Route::controller(ProductsDashboardController::class)->group(function () {
            Route::get('/dashboard/statistics', 'statistics')->name('statistics');
        });
        
        Route::controller(ProductController::class)->group(function () {
            Route::post('allProducts', 'index');
            Route::get('products/{id}', 'show');
            Route::put('products/{id}', 'update');
            Route::patch('products/{id}/status', 'changeStatus');
        });

        Route::controller(VendorsController::class)->group(function () {
            Route::post('allVendors', 'index');
            Route::get('vendors/{id}', 'show');
            Route::put('vendors/{id}', 'update');
            Route::patch('vendors/{id}/status', 'changeStatus');
        });

        Route::controller(CollectionsController::class)->group(function () {
            Route::post('allCollections', 'index');
            Route::get('collections/{id}', 'show');
            Route::put('collections/{id}', 'update');
            Route::patch('collections/{id}/status', 'changeStatus');
        });

        Route::controller(ProductTypesController::class)->group(function () {
            Route::post('allProductTypes', 'index');
            Route::get('product-types/{id}', 'show');
            Route::put('product-types/{id}', 'update');
        });

        Route::controller(OptionsController::class)->group(function () {
            Route::post('allOptions', 'index');
            Route::get('options/{id}', 'show');
            Route::put('options/{id}', 'update');
        });

        Route::controller(CategoriesController::class)->group(function () {
            Route::post('allCategories', 'index');
            Route::get('categories/{id}', 'show');
            Route::put('categories/{id}', 'update');
        });

        Route::controller(TagsController::class)->group(function () {
            Route::post('allTags', 'index');
            Route::get('tags/{id}', 'show');
            Route::put('tags/{id}', 'update');
        });
    });
});
