<?php

namespace App\Modules\Catalog\Providers;

use App\Modules\Catalog\Repositories\Eloquent\References\FrontendCategoriesRepository;
use App\Modules\Catalog\Repositories\Eloquent\References\FrontendCollectionsRepository;
use App\Modules\Catalog\Repositories\Eloquent\References\FrontendOptionsRepository;
use App\Modules\Catalog\Repositories\Eloquent\References\FrontendProductTypesRepository;
use App\Modules\Catalog\Repositories\Eloquent\References\FrontendTagsRepository;
use App\Modules\Catalog\Repositories\Eloquent\References\FrontendVendorsRepository;
use App\Modules\Catalog\Repositories\Eloquent\ProductsDashboardRepository;
use App\Modules\Catalog\Repositories\Interfaces\References\CategoriesRepositoryInterface;
use App\Modules\Catalog\Repositories\Interfaces\References\CollectionsRepositoryInterface;
use App\Modules\Catalog\Repositories\Interfaces\References\OptionsRepositoryInterface;
use App\Modules\Catalog\Repositories\Interfaces\References\ProductTypesRepositoryInterface;
use App\Modules\Catalog\Repositories\Interfaces\References\TagsRepositoryInterface;
use App\Modules\Catalog\Repositories\Interfaces\References\VendorsRepositoryInterface;
use App\Modules\Catalog\Repositories\Interfaces\ProductsDashboardRepositoryInterface;
use App\Modules\Catalog\Repositories\Eloquent\FrontendProductsRepository;
use App\Modules\Catalog\Repositories\Interfaces\ProductsRepositoryInterface;
use Illuminate\Support\ServiceProvider;

class CatalogServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(ProductsDashboardRepositoryInterface::class, ProductsDashboardRepository::class);
        $this->app->bind(ProductsRepositoryInterface::class, FrontendProductsRepository::class);
        $this->app->bind(VendorsRepositoryInterface::class, FrontendVendorsRepository::class);
        $this->app->bind(CollectionsRepositoryInterface::class, FrontendCollectionsRepository::class);
        $this->app->bind(ProductTypesRepositoryInterface::class, FrontendProductTypesRepository::class);
        $this->app->bind(OptionsRepositoryInterface::class, FrontendOptionsRepository::class);
        $this->app->bind(CategoriesRepositoryInterface::class, FrontendCategoriesRepository::class);
        $this->app->bind(TagsRepositoryInterface::class, FrontendTagsRepository::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
    }
}
