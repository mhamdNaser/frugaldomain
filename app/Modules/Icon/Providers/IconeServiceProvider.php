<?php

namespace App\Modules\Icon\Providers;

use App\Modules\Icon\Repositories\Eloquent\IconRepository;
use App\Modules\Icon\Repositories\Eloquent\IconCategoryRepository;
use App\Modules\Icon\Repositories\Interfaces\IconRepositoryInterface;
use App\Modules\Icon\Repositories\Interfaces\IconCategoryRepositoryInterface;
use Illuminate\Support\ServiceProvider;

class IconeServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(IconRepositoryInterface::class,IconRepository::class);
        $this->app->bind(IconCategoryRepositoryInterface::class, IconCategoryRepository::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
    }
}
