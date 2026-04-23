<?php

namespace App\Modules\Core\Providers;

use App\Modules\Core\Repositories\Interfaces\DashboardRepositoryInterface;
use App\Modules\Core\Repositories\Interfaces\SyncMonitorRepositoryInterface;
use App\Modules\Core\Repositories\Interfaces\WebhookLogsRepositoryInterface;
use App\Modules\Core\Repositories\Interfaces\WebhookSubscriptionsRepositoryInterface;
use App\Modules\Core\Repositories\Eloquent\DashboardRepository;
use App\Modules\Core\Repositories\Eloquent\SyncMonitorRepository;
use App\Modules\Core\Repositories\Eloquent\FrontendWebhookLogsRepository;
use App\Modules\Core\Repositories\Eloquent\FrontendWebhookSubscriptionsRepository;
use Illuminate\Support\ServiceProvider;

class CoreServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(DashboardRepositoryInterface::class, DashboardRepository::class);
        $this->app->bind(SyncMonitorRepositoryInterface::class, SyncMonitorRepository::class);
        $this->app->bind(WebhookLogsRepositoryInterface::class, FrontendWebhookLogsRepository::class);
        $this->app->bind(WebhookSubscriptionsRepositoryInterface::class, FrontendWebhookSubscriptionsRepository::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
    }
}
