<?php

namespace App\Modules\Shipping\Providers;

use App\Modules\Shipping\Repositories\Eloquent\FrontendShippingZonesRepository;
use App\Modules\Shipping\Repositories\Interfaces\ShippingZonesRepositoryInterface;
use Illuminate\Support\ServiceProvider;

class ShippingServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(ShippingZonesRepositoryInterface::class, FrontendShippingZonesRepository::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
    }
}
