<?php

namespace App\Modules\Marketing\Providers;

use App\Modules\Marketing\Repositories\Eloquent\FrontendDiscountCodesRepository;
use App\Modules\Marketing\Repositories\Eloquent\FrontendDiscountsRepository;
use App\Modules\Marketing\Repositories\Eloquent\FrontendDiscountUsagesRepository;
use App\Modules\Marketing\Repositories\Eloquent\FrontendExchangesRepository;
use App\Modules\Marketing\Repositories\Eloquent\FrontendMarketsRepository;
use App\Modules\Marketing\Repositories\Eloquent\FrontendSellingPlanGroupsRepository;
use App\Modules\Marketing\Repositories\Eloquent\FrontendSellingPlansRepository;
use App\Modules\Marketing\Repositories\Eloquent\FrontendSellingPlanSubscriptionsRepository;
use App\Modules\Marketing\Repositories\Interfaces\DiscountCodesRepositoryInterface;
use App\Modules\Marketing\Repositories\Interfaces\DiscountsRepositoryInterface;
use App\Modules\Marketing\Repositories\Interfaces\DiscountUsagesRepositoryInterface;
use App\Modules\Marketing\Repositories\Interfaces\ExchangesRepositoryInterface;
use App\Modules\Marketing\Repositories\Interfaces\MarketsRepositoryInterface;
use App\Modules\Marketing\Repositories\Interfaces\SellingPlanGroupsRepositoryInterface;
use App\Modules\Marketing\Repositories\Interfaces\SellingPlansRepositoryInterface;
use App\Modules\Marketing\Repositories\Interfaces\SellingPlanSubscriptionsRepositoryInterface;
use Illuminate\Support\ServiceProvider;

class MarketingServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(DiscountsRepositoryInterface::class, FrontendDiscountsRepository::class);
        $this->app->bind(DiscountCodesRepositoryInterface::class, FrontendDiscountCodesRepository::class);
        $this->app->bind(DiscountUsagesRepositoryInterface::class, FrontendDiscountUsagesRepository::class);
        $this->app->bind(ExchangesRepositoryInterface::class, FrontendExchangesRepository::class);
        $this->app->bind(MarketsRepositoryInterface::class, FrontendMarketsRepository::class);
        $this->app->bind(SellingPlanGroupsRepositoryInterface::class, FrontendSellingPlanGroupsRepository::class);
        $this->app->bind(SellingPlansRepositoryInterface::class, FrontendSellingPlansRepository::class);
        $this->app->bind(SellingPlanSubscriptionsRepositoryInterface::class, FrontendSellingPlanSubscriptionsRepository::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
    }
}
