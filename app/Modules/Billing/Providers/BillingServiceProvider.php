<?php

namespace App\Modules\Billing\Providers;

use App\Repositories\Eloquent\UserRepository;
use App\Repositories\Eloquent\LanguageRepository;
use App\Repositories\Eloquent\AdminRoleRepository;
use App\Repositories\Eloquent\IconRepository;
use App\Repositories\Eloquent\IconCategoryRepository;
use App\Repositories\Interfaces\UserRepositoryInterface;
use App\Repositories\Interfaces\LanguageRepositoryInterface;
use App\Repositories\Interfaces\AdminRoleRepositoryInterface;
use App\Repositories\Interfaces\IconRepositoryInterface;
use App\Repositories\Interfaces\IconCategoryRepositoryInterface;
use Illuminate\Support\ServiceProvider;
use App\Repositories\Interfaces\PermissionsRepositoryInterface;
use App\Repositories\Eloquent\PermissionsRepository;

class BillingServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(UserRepositoryInterface::class, UserRepository::class);
        $this->app->bind(LanguageRepositoryInterface::class, LanguageRepository::class);
        $this->app->bind(AdminRoleRepositoryInterface::class, AdminRoleRepository::class);
        $this->app->bind(IconRepositoryInterface::class,IconRepository::class);
        $this->app->bind(IconCategoryRepositoryInterface::class, IconCategoryRepository::class);
        $this->app->bind(PermissionsRepositoryInterface::class, PermissionsRepository::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
    }
}
