<?php

namespace App\Modules\Stores\Providers;

use App\Modules\Stores\Repositories\Eloquent\StoreRepository;
use App\Modules\Stores\Repositories\Interfaces\StoreRepositoryInterface;
use Illuminate\Support\ServiceProvider;

class StoreServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void {
        $this->app->bind(StoreRepositoryInterface::class , StoreRepository::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
    }
}
