<?php

use App\Modules\Catalog\Controllers\References\CategoriesController;
use App\Modules\Catalog\Controllers\References\CollectionsController;
use App\Modules\Catalog\Controllers\References\OptionsController;
use App\Modules\Catalog\Controllers\References\ProductTypesController;
use App\Modules\Catalog\Controllers\References\TagsController;
use App\Modules\Catalog\Controllers\References\VendorsController;
use App\Modules\Catalog\Controllers\ProductController;
use App\Modules\Catalog\Controllers\ProductVariantController;
use App\Modules\Catalog\Controllers\ProductsDashboardController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin')->group(function () {

    Route::middleware(['auth:sanctum', 'role:partner'])->group(function () {

        Route::controller(ProductsDashboardController::class)->group(function () {
            Route::get('/dashboard/statistics', 'statistics')->name('statistics');
        });
        
        Route::controller(ProductController::class)->group(function () {
            Route::post('allProducts', 'index');
            Route::post('products', 'store');
            Route::get('products/{id}', 'show');
            Route::put('products/{id}', 'update');
            Route::patch('products/{id}/variants/price', 'updateVariantsPrice');
            Route::delete('products/{product}', 'destroy');
            Route::patch('products/{id}/status', 'changeStatus');
        });

        Route::controller(ProductVariantController::class)->group(function () {
            Route::post('allVariants', 'index');
            Route::post('variants', 'store');
            Route::get('variants/{id}', 'show');
            Route::put('variants/{id}', 'update');
            Route::delete('variants/{id}', 'destroy');
        });

        Route::controller(VendorsController::class)->group(function () {
            Route::post('allVendors', 'index');
            Route::post('vendors', 'store');
            Route::get('vendors/{id}', 'show');
            Route::put('vendors/{id}', 'update');
            Route::delete('vendors/{id}', 'destroy');
            Route::patch('vendors/{id}/status', 'changeStatus');
        });

        Route::controller(CollectionsController::class)->group(function () {
            Route::post('allCollections', 'index');
            Route::post('collections', 'store');
            Route::get('collections/{id}', 'show');
            Route::put('collections/{id}', 'update');
            Route::delete('collections/{id}', 'destroy');
            Route::patch('collections/{id}/status', 'changeStatus');
        });

        Route::controller(ProductTypesController::class)->group(function () {
            Route::post('allProductTypes', 'index');
            Route::post('product-types', 'store');
            Route::get('product-types/{id}', 'show');
            Route::put('product-types/{id}', 'update');
            Route::delete('product-types/{id}', 'destroy');
        });

        Route::controller(OptionsController::class)->group(function () {
            Route::post('allOptions', 'index');
            Route::post('options', 'store');
            Route::get('options/{id}', 'show');
            Route::put('options/{id}', 'update');
            Route::delete('options/{id}', 'destroy');
        });

        Route::controller(CategoriesController::class)->group(function () {
            Route::post('allCategories', 'index');
            Route::post('categories', 'store');
            Route::get('categories/{id}', 'show');
            Route::put('categories/{id}', 'update');
            Route::delete('categories/{id}', 'destroy');
        });

        Route::controller(TagsController::class)->group(function () {
            Route::post('allTags', 'index');
            Route::post('tags', 'store');
            Route::get('tags/{id}', 'show');
            Route::put('tags/{id}', 'update');
            Route::delete('tags/{id}', 'destroy');
        });
    });
});
