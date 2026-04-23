<?php

namespace App\Modules\Stores\Providers;

use App\Modules\Stores\Repositories\Eloquent\FrontendStoreBrandingsRepository;
use App\Modules\Stores\Repositories\Eloquent\FrontendStoreSettingsRepository;
use App\Modules\Stores\Repositories\Eloquent\StoreRepository;
use App\Modules\Stores\Repositories\Interfaces\StoreBrandingsRepositoryInterface;
use App\Modules\Stores\Repositories\Interfaces\StoreRepositoryInterface;
use App\Modules\Stores\Repositories\Interfaces\StoreSettingsRepositoryInterface;
use Illuminate\Support\ServiceProvider;

class StoreServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void {
        $this->app->bind(StoreRepositoryInterface::class , StoreRepository::class);
        $this->app->bind(StoreSettingsRepositoryInterface::class, FrontendStoreSettingsRepository::class);
        $this->app->bind(StoreBrandingsRepositoryInterface::class, FrontendStoreBrandingsRepository::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
    }
}
