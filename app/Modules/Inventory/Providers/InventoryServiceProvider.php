<?php

namespace App\Modules\Inventory\Providers;

use App\Modules\Catalog\Repositories\Eloquent\ProductsDashboardRepository;
use App\Modules\Catalog\Repositories\Interfaces\ProductsDashboardRepositoryInterface;
use App\Modules\Catalog\Repositories\Eloquent\FrontendProductsRepository;
use App\Modules\Catalog\Repositories\Interfaces\ProductsRepositoryInterface;
use Illuminate\Support\ServiceProvider;

class InventoryServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(ProductsDashboardRepositoryInterface::class, ProductsDashboardRepository::class);
        $this->app->bind(ProductsRepositoryInterface::class, FrontendProductsRepository::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
    }
}
