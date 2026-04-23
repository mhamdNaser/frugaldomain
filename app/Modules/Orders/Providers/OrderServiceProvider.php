<?php

namespace App\Modules\Orders\Providers;

use App\Modules\Orders\Repositories\Eloquent\FrontendCartItemsRepository;
use App\Modules\Orders\Repositories\Eloquent\FrontendCartsRepository;
use App\Modules\Orders\Repositories\Eloquent\FrontendDraftOrderItemsRepository;
use App\Modules\Orders\Repositories\Eloquent\FrontendDraftOrdersRepository;
use App\Modules\Orders\Repositories\Eloquent\FrontendOrderDutiesRepository;
use App\Modules\Orders\Repositories\Eloquent\FrontendOrderItemsRepository;
use App\Modules\Orders\Repositories\Eloquent\FrontendOrderItemDutiesRepository;
use App\Modules\Orders\Repositories\Eloquent\FrontendOrderReturnItemsRepository;
use App\Modules\Orders\Repositories\Eloquent\FrontendOrderReturnsRepository;
use App\Modules\Orders\Repositories\Eloquent\FrontendOrdersRepository;
use App\Modules\Orders\Repositories\Interfaces\CartItemsRepositoryInterface;
use App\Modules\Orders\Repositories\Interfaces\CartsRepositoryInterface;
use App\Modules\Orders\Repositories\Interfaces\DraftOrderItemsRepositoryInterface;
use App\Modules\Orders\Repositories\Interfaces\DraftOrdersRepositoryInterface;
use App\Modules\Orders\Repositories\Interfaces\OrderDutiesRepositoryInterface;
use App\Modules\Orders\Repositories\Interfaces\OrderItemsRepositoryInterface;
use App\Modules\Orders\Repositories\Interfaces\OrderItemDutiesRepositoryInterface;
use App\Modules\Orders\Repositories\Interfaces\OrderReturnItemsRepositoryInterface;
use App\Modules\Orders\Repositories\Interfaces\OrderReturnsRepositoryInterface;
use App\Modules\Orders\Repositories\Interfaces\OrdersRepositoryInterface;
use Illuminate\Support\ServiceProvider;

class OrderServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(OrdersRepositoryInterface::class, FrontendOrdersRepository::class);
        $this->app->bind(OrderItemsRepositoryInterface::class, FrontendOrderItemsRepository::class);
        $this->app->bind(DraftOrdersRepositoryInterface::class, FrontendDraftOrdersRepository::class);
        $this->app->bind(DraftOrderItemsRepositoryInterface::class, FrontendDraftOrderItemsRepository::class);
        $this->app->bind(CartsRepositoryInterface::class, FrontendCartsRepository::class);
        $this->app->bind(CartItemsRepositoryInterface::class, FrontendCartItemsRepository::class);
        $this->app->bind(OrderDutiesRepositoryInterface::class, FrontendOrderDutiesRepository::class);
        $this->app->bind(OrderItemDutiesRepositoryInterface::class, FrontendOrderItemDutiesRepository::class);
        $this->app->bind(OrderReturnsRepositoryInterface::class, FrontendOrderReturnsRepository::class);
        $this->app->bind(OrderReturnItemsRepositoryInterface::class, FrontendOrderReturnItemsRepository::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
    }
}
