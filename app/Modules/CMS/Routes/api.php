<?php

use App\Modules\CMS\Controllers\ArticleController;
use App\Modules\CMS\Controllers\BlogController;
use App\Modules\CMS\Controllers\FileController;
use App\Modules\CMS\Controllers\MenuController;
use App\Modules\CMS\Controllers\MenuItemController;
use App\Modules\CMS\Controllers\MetaDefinitionsController;
use App\Modules\CMS\Controllers\MetaDefinitionsFiledsController;
use App\Modules\CMS\Controllers\MetafieldController;
use App\Modules\CMS\Controllers\MetafieldMetaobjectController;
use App\Modules\CMS\Controllers\MetaobjectController;
use App\Modules\CMS\Controllers\PageController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin')->group(function () {
    Route::middleware(['auth:sanctum', 'role:partner|admin'])->group(function () {
        Route::post('files', [FileController::class, 'index']);
        Route::post('files/upload-to-shopify', [FileController::class, 'uploadToShopify']);
        Route::post('files/attach', [FileController::class, 'attach']);
        Route::controller(ArticleController::class)->group(function () {
            Route::post('articles', 'index');
            Route::post('articles/store', 'store');
            Route::get('articles/{id}', 'show');
            Route::put('articles/{id}', 'update');
            Route::delete('articles/{id}', 'destroy');
        });
        Route::controller(BlogController::class)->group(function () {
            Route::post('blogs', 'index');
            Route::post('blogs/store', 'store');
            Route::get('blogs/{id}', 'show');
            Route::put('blogs/{id}', 'update');
            Route::delete('blogs/{id}', 'destroy');
        });
        Route::controller(MenuController::class)->group(function () {
            Route::post('menus', 'index');
            Route::post('menus/store', 'store');
            Route::get('menus/{id}', 'show');
            Route::put('menus/{id}', 'update');
            Route::delete('menus/{id}', 'destroy');
        });
        Route::post('menu-items', [MenuItemController::class, 'index']);
        Route::controller(MetafieldController::class)->group(function () {
            Route::post('metafields', 'index');
            Route::post('metafields/store', 'store');
            Route::get('metafields/{id}', 'show');
            Route::put('metafields/{id}', 'update');
            Route::delete('metafields/{id}', 'destroy');
        });
        Route::post('metafield-metaobjects', [MetafieldMetaobjectController::class, 'index']);
        Route::controller(MetaobjectController::class)->group(function () {
            Route::post('metaobjects', 'index');
            Route::post('metaobjects/store', 'store');
            Route::get('metaobjects/{id}', 'show');
            Route::put('metaobjects/{id}', 'update');
            Route::delete('metaobjects/{id}', 'destroy');
        });
        Route::post('meta-definitions', [MetaDefinitionsController::class, 'index']);
        Route::post('meta-definitions-fields', [MetaDefinitionsFiledsController::class, 'index']);
        Route::controller(PageController::class)->group(function () {
            Route::post('pages', 'index');
            Route::post('pages/store', 'store');
            Route::get('pages/{id}', 'show');
            Route::put('pages/{id}', 'update');
            Route::delete('pages/{id}', 'destroy');
        });
    });
});
