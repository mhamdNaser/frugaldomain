<?php

namespace App\Modules\Shopify\Providers;

use App\Modules\Shopify\OutboundSync\Console\Commands\DispatchDueOutboundSyncsCommand;
use Illuminate\Support\ServiceProvider;

class ShopifyServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        if ($this->app->runningInConsole()) {
            $this->commands([
                DispatchDueOutboundSyncsCommand::class,
            ]);
        }
    }
}

