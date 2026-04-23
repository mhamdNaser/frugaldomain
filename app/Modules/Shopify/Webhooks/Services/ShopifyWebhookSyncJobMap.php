<?php

namespace App\Modules\Shopify\Webhooks\Services;

use App\Modules\Shopify\Jobs\SyncContentJob;
use App\Modules\Shopify\Jobs\SyncCustomerMarketingConsentJob;
use App\Modules\Shopify\Jobs\SyncCustomersJob;
use App\Modules\Shopify\Jobs\SyncDiscountsJob;
use App\Modules\Shopify\Jobs\SyncDraftOrdersJob;
use App\Modules\Shopify\Jobs\SyncFulfillmentsJob;
use App\Modules\Shopify\Jobs\SyncGlobalFilesJob;
use App\Modules\Shopify\Jobs\SyncInventoryStatesJob;
use App\Modules\Shopify\Jobs\SyncMarketsPriceListsJob;
use App\Modules\Shopify\Jobs\SyncMetaobjectDefinitionsJob;
use App\Modules\Shopify\Jobs\SyncOrderDutiesJob;
use App\Modules\Shopify\Jobs\SyncOrderFinancialsJob;
use App\Modules\Shopify\Jobs\SyncOrderRiskAndChannelsJob;
use App\Modules\Shopify\Jobs\SyncOrdersJob;
use App\Modules\Shopify\Jobs\SyncProductAdvancedMediaJob;
use App\Modules\Shopify\Jobs\SyncReturnsExchangesReverseJob;
use App\Modules\Shopify\Jobs\SyncSellingPlansJob;
use App\Modules\Shopify\Jobs\SyncShippingProfilesJob;
use App\Modules\Shopify\Jobs\SyncShopifyStoreDetailsJob;
use App\Modules\Shopify\Jobs\SyncStoreInstallsJob;
use App\Modules\Shopify\Jobs\SyncWebhookLogsJob;
use App\Modules\Shopify\Jobs\SyncWebhookSubscriptionsJob;

class ShopifyWebhookSyncJobMap
{
    public function jobClassFor(string $syncType): ?string
    {
        return $this->map()[$syncType] ?? null;
    }

    /**
     * @return array<string, class-string>
     */
    private function map(): array
    {
        return [
            'shop-details' => SyncShopifyStoreDetailsJob::class,
            'store-installs' => SyncStoreInstallsJob::class,
            'files' => SyncGlobalFilesJob::class,
            'customers' => SyncCustomersJob::class,
            'orders' => SyncOrdersJob::class,
            'draft-orders' => SyncDraftOrdersJob::class,
            'fulfillments' => SyncFulfillmentsJob::class,
            'financials' => SyncOrderFinancialsJob::class,
            'discounts' => SyncDiscountsJob::class,
            'content' => SyncContentJob::class,
            'webhook-subscriptions' => SyncWebhookSubscriptionsJob::class,
            'webhook-logs' => SyncWebhookLogsJob::class,
            'shipping' => SyncShippingProfilesJob::class,
            'returns-exchanges-reverse' => SyncReturnsExchangesReverseJob::class,
            'order-risk-channel' => SyncOrderRiskAndChannelsJob::class,
            'order-duties' => SyncOrderDutiesJob::class,
            'inventory-states' => SyncInventoryStatesJob::class,
            'customer-marketing-consent' => SyncCustomerMarketingConsentJob::class,
            'product-advanced-media' => SyncProductAdvancedMediaJob::class,
            'markets-price-lists' => SyncMarketsPriceListsJob::class,
            'metaobject-definitions' => SyncMetaobjectDefinitionsJob::class,
            'selling-plans' => SyncSellingPlansJob::class,
        ];
    }
}

