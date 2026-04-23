<?php

namespace App\Modules\User\Providers;

use App\Modules\User\Repositories\Eloquent\UserRepository;
use App\Modules\User\Repositories\Eloquent\AdminRoleRepository;
use App\Modules\User\Repositories\Eloquent\CustomerRepository;
use App\Modules\User\Repositories\Interfaces\UserRepositoryInterface;
use App\Modules\User\Repositories\Interfaces\AdminRoleRepositoryInterface;
use App\Modules\User\Repositories\Interfaces\CustomerRepositoryInterface;
use Illuminate\Support\ServiceProvider;
use App\Modules\User\Repositories\Interfaces\PermissionsRepositoryInterface;
use App\Modules\User\Repositories\Eloquent\PermissionsRepository;

class UserServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(UserRepositoryInterface::class, UserRepository::class);
        $this->app->bind(AdminRoleRepositoryInterface::class, AdminRoleRepository::class);
        $this->app->bind(PermissionsRepositoryInterface::class, PermissionsRepository::class);
        $this->app->bind(CustomerRepositoryInterface::class, CustomerRepository::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
    }
}
