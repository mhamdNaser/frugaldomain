<?php

namespace App\Modules\Analytics\Providers;

use Illuminate\Support\ServiceProvider;

class AnalyticsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    /**
     * Routes are picked up automatically by routes/api.php (it requires every
     * app/Modules/<Name>/Routes/api.php), so only the migrations live here.
     */
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
    }
}
