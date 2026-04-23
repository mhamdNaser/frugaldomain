<?php

namespace App\Modules\Shopify\Webhooks\Handlers;

use App\Modules\Shopify\Webhooks\DTOs\WebhookData;
use App\Modules\Shopify\Webhooks\Jobs\SyncSingleCollectionJob;

class CollectionPublishWebhookHandler implements WebhookHandlerInterface
{
    public function handle(WebhookData $data): void
    {
        $collectionId = $data->payload['id'] ?? null;

        if (!$data->storeId || !$collectionId) {
            return;
        }

        SyncSingleCollectionJob::dispatch(
            storeId: $data->storeId,
            shopifyCollectionId: (string) $collectionId,
            webhookExternalId: $data->externalId,
        )->onQueue('shopify-sync');
    }
}

