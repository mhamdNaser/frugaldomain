<?php

return [
    App\Modules\Billing\Providers\BillingServiceProvider::class,
    App\Modules\Catalog\Providers\CatalogServiceProvider::class,
    App\Modules\CMS\Providers\CMSServiceProvider::class,
    App\Modules\Core\Providers\CoreServiceProvider::class,
    App\Modules\Fulfillment\Providers\FulfillmentServiceProvider::class,
    App\Modules\Gesture\Providers\GestureServiceProvider::class,
    App\Modules\Icon\Providers\IconeServiceProvider::class,
    App\Modules\Inventory\Providers\InventoryServiceProvider::class,
    App\Modules\Locale\Providers\LocaleServiceProvider::class,
    App\Modules\Marketing\Providers\MarketingServiceProvider::class,
    App\Modules\Orders\Providers\OrderServiceProvider::class,
    App\Modules\Shipping\Providers\ShippingServiceProvider::class,
    App\Modules\Stores\Providers\StoreServiceProvider::class,
    App\Modules\Tax\Providers\TaxServiceProvider::class,
    App\Modules\User\Providers\UserServiceProvider::class,
    App\Providers\Filament\AdminPanelProvider::class,
];
