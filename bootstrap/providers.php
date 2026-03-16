<?php

return [
    App\Modules\Billing\Providers\BillingServiceProvider::class,
    App\Modules\Core\Providers\CoreServiceProvider::class,
    App\Modules\Icon\Providers\IconeServiceProvider::class,
    App\Modules\Locale\Providers\LocaleServiceProvider::class,
    App\Modules\Orders\Providers\OrderServiceProvider::class,
    App\Modules\Products\Providers\ProductServiceProvider::class,
    App\Modules\Shopify\Providers\ShopifyServiceProvider::class,
    App\Modules\Stores\Providers\StoreServiceProvider::class,
    App\Modules\User\Providers\UserServiceProvider::class,
    App\Providers\Filament\AdminPanelProvider::class,
    App\Modules\Gesture\Providers\GestureServiceProvider::class,
];
