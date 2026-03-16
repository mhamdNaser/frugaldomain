<?php

namespace App\Modules\Locale\Providers;

use App\Modules\Locale\Repositories\Eloquent\CityRepository;
use App\Modules\Locale\Repositories\Eloquent\CountryRepository;
use App\Modules\Locale\Repositories\Eloquent\LanguageRepository;
use App\Modules\Locale\Repositories\Eloquent\StateRepository;
use App\Modules\Locale\Repositories\Interfaces\CityRepositoryInterface;
use App\Modules\Locale\Repositories\Interfaces\CountryRepositoryInterface;
use App\Modules\Locale\Repositories\Interfaces\LanguageRepositoryInterface;
use App\Modules\Locale\Repositories\Interfaces\StateRepositoryInterface;
use Illuminate\Support\ServiceProvider;

class LocaleServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(LanguageRepositoryInterface::class, LanguageRepository::class);
        $this->app->bind(CountryRepositoryInterface::class, CountryRepository::class);
        $this->app->bind(StateRepositoryInterface::class, StateRepository::class);
        $this->app->bind(CityRepositoryInterface::class, CityRepository::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
    }
}
