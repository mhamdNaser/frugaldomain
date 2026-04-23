<?php

namespace App\Modules\Billing\Providers;

use App\Modules\Billing\Repositories\Eloquent\FrontendPaymentTransactionsRepository;
use App\Modules\Billing\Repositories\Eloquent\FrontendPlansRepository;
use App\Modules\Billing\Repositories\Eloquent\FrontendRefundItemsRepository;
use App\Modules\Billing\Repositories\Eloquent\FrontendRefundsRepository;
use App\Modules\Billing\Repositories\Eloquent\FrontendSubscriptionsRepository;
use App\Modules\Billing\Repositories\Interfaces\PaymentTransactionsRepositoryInterface;
use App\Modules\Billing\Repositories\Interfaces\PlansRepositoryInterface;
use App\Modules\Billing\Repositories\Interfaces\RefundItemsRepositoryInterface;
use App\Modules\Billing\Repositories\Interfaces\RefundsRepositoryInterface;
use App\Modules\Billing\Repositories\Interfaces\SubscriptionsRepositoryInterface;
use Illuminate\Support\ServiceProvider;

class BillingServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(PaymentTransactionsRepositoryInterface::class, FrontendPaymentTransactionsRepository::class);
        $this->app->bind(RefundsRepositoryInterface::class, FrontendRefundsRepository::class);
        $this->app->bind(RefundItemsRepositoryInterface::class, FrontendRefundItemsRepository::class);
        $this->app->bind(PlansRepositoryInterface::class, FrontendPlansRepository::class);
        $this->app->bind(SubscriptionsRepositoryInterface::class, FrontendSubscriptionsRepository::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
    }
}
