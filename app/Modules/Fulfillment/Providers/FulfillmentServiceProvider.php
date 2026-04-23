<?php

namespace App\Modules\Fulfillment\Providers;

use App\Modules\Fulfillment\Repositories\Eloquent\FrontendFulfillmentItemsRepository;
use App\Modules\Fulfillment\Repositories\Eloquent\FrontendFulfillmentOrderItemsRepository;
use App\Modules\Fulfillment\Repositories\Eloquent\FrontendFulfillmentOrdersRepository;
use App\Modules\Fulfillment\Repositories\Eloquent\FrontendFulfillmentsRepository;
use App\Modules\Fulfillment\Repositories\Eloquent\FrontendFulfillmentServicesRepository;
use App\Modules\Fulfillment\Repositories\Eloquent\FrontendFulfillmentTrackingRepository;
use App\Modules\Fulfillment\Repositories\Eloquent\FrontendReverseFulfillmentsRepository;
use App\Modules\Fulfillment\Repositories\Interfaces\FulfillmentItemsRepositoryInterface;
use App\Modules\Fulfillment\Repositories\Interfaces\FulfillmentOrderItemsRepositoryInterface;
use App\Modules\Fulfillment\Repositories\Interfaces\FulfillmentOrdersRepositoryInterface;
use App\Modules\Fulfillment\Repositories\Interfaces\FulfillmentsRepositoryInterface;
use App\Modules\Fulfillment\Repositories\Interfaces\FulfillmentServicesRepositoryInterface;
use App\Modules\Fulfillment\Repositories\Interfaces\FulfillmentTrackingRepositoryInterface;
use App\Modules\Fulfillment\Repositories\Interfaces\ReverseFulfillmentsRepositoryInterface;
use Illuminate\Support\ServiceProvider;

class FulfillmentServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(FulfillmentsRepositoryInterface::class, FrontendFulfillmentsRepository::class);
        $this->app->bind(FulfillmentItemsRepositoryInterface::class, FrontendFulfillmentItemsRepository::class);
        $this->app->bind(FulfillmentOrdersRepositoryInterface::class, FrontendFulfillmentOrdersRepository::class);
        $this->app->bind(FulfillmentOrderItemsRepositoryInterface::class, FrontendFulfillmentOrderItemsRepository::class);
        $this->app->bind(FulfillmentServicesRepositoryInterface::class, FrontendFulfillmentServicesRepository::class);
        $this->app->bind(FulfillmentTrackingRepositoryInterface::class, FrontendFulfillmentTrackingRepository::class);
        $this->app->bind(ReverseFulfillmentsRepositoryInterface::class, FrontendReverseFulfillmentsRepository::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
    }
}
