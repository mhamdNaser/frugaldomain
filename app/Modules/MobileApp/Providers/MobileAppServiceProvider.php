<?php

namespace App\Modules\MobileApp\Providers;

use App\Modules\MobileApp\Repositories\Eloquent\MobileStorefrontRepository;
use App\Modules\MobileApp\Repositories\Interfaces\MobileStorefrontRepositoryInterface;
use Illuminate\Support\ServiceProvider;

class MobileAppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(MobileStorefrontRepositoryInterface::class, MobileStorefrontRepository::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
    }
}
