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
        Route::controller(ArticleController::class)->group(function () {
            Route::post('articles', 'index');
            Route::get('articles/{id}', 'show');
        });
        Route::post('blogs', [BlogController::class, 'index']);
        Route::post('menus', [MenuController::class, 'index']);
        Route::post('menu-items', [MenuItemController::class, 'index']);
        Route::post('metafields', [MetafieldController::class, 'index']);
        Route::post('metafield-metaobjects', [MetafieldMetaobjectController::class, 'index']);
        Route::post('metaobjects', [MetaobjectController::class, 'index']);
        Route::post('meta-definitions', [MetaDefinitionsController::class, 'index']);
        Route::post('meta-definitions-fields', [MetaDefinitionsFiledsController::class, 'index']);
        Route::post('pages', [PageController::class, 'index']);
    });
});
