<?php

namespace App\Modules\Core\Providers;

use App\Modules\Core\Repositories\Interfaces\DashboardRepositoryInterface;
use App\Modules\Core\Repositories\Eloquent\DashboardRepository;
use Illuminate\Support\ServiceProvider;

class CoreServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(DashboardRepositoryInterface::class, DashboardRepository::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
    }
}
