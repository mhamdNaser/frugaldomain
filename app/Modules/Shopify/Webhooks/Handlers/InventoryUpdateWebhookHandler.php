<?php

namespace App\Modules\Shopify\Webhooks\Handlers;

use App\Modules\Shopify\Webhooks\DTOs\WebhookData;
use App\Modules\Shopify\Webhooks\Jobs\DispatchSyncTypeFromWebhookJob;
use App\Modules\Shopify\Webhooks\Jobs\UpdateInventoryFromWebhookJob;

class InventoryUpdateWebhookHandler implements WebhookHandlerInterface
{
    public function handle(WebhookData $data): void
    {
        $inventoryItemId = $data->payload['inventory_item_id'] ?? null;
        $locationId = $data->payload['location_id'] ?? null;
        $available = $data->payload['available'] ?? null;
        $updatedAt = $data->payload['updated_at'] ?? null;

        if (!$data->storeId || !$inventoryItemId || $available === null) {
            return;
        }

        UpdateInventoryFromWebhookJob::dispatch(
            storeId: $data->storeId,
            inventoryItemId: (string) $inventoryItemId,
            locationId: $locationId ? (string) $locationId : null,
            available: (int) $available,
            updatedAt: is_string($updatedAt) ? $updatedAt : null,
            webhookExternalId: $data->externalId,
        )->onQueue('shopify-inventory');

        DispatchSyncTypeFromWebhookJob::dispatch(
            storeId: $data->storeId,
            syncType: 'inventory-states',
            webhookExternalId: $data->externalId,
            topic: $data->topic,
        )->onQueue('shopify-sync');
    }
}
