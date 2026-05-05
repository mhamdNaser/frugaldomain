<?php

namespace App\Modules\Inventory\Providers;

use App\Modules\Inventory\Repositories\Eloquent\FrontendInventoriesRepository;
use App\Modules\Inventory\Repositories\Interfaces\InventoriesRepositoryInterface;
use Illuminate\Support\ServiceProvider;

class InventoryServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(InventoriesRepositoryInterface::class, FrontendInventoriesRepository::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
    }
}
