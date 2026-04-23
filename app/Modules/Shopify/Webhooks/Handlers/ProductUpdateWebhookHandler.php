<?php

namespace App\Modules\Shopify\Webhooks\Handlers;

use App\Modules\Shopify\Webhooks\DTOs\WebhookData;
use App\Modules\Shopify\Webhooks\Jobs\DispatchSyncTypeFromWebhookJob;
use App\Modules\Shopify\Webhooks\Jobs\SyncSingleProductJob;

class ProductUpdateWebhookHandler implements WebhookHandlerInterface
{
    public function handle(WebhookData $data): void
    {
        $productId = $data->payload['id'] ?? null;

        if (!$data->storeId || !$productId) {
            return;
        }

        SyncSingleProductJob::dispatch(
            storeId: $data->storeId,
            shopifyProductId: (string) $productId,
            webhookExternalId: $data->externalId,
        )->onQueue('shopify-sync');

        DispatchSyncTypeFromWebhookJob::dispatch(
            storeId: $data->storeId,
            syncType: 'product-advanced-media',
            webhookExternalId: $data->externalId,
            topic: $data->topic,
        )->onQueue('shopify-sync');
    }
}
