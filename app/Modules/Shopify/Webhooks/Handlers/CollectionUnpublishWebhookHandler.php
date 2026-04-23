<?php

namespace App\Modules\Shopify\Webhooks\Handlers;

use App\Modules\Catalog\Models\Collection;
use App\Modules\Shopify\Webhooks\DTOs\WebhookData;

class CollectionUnpublishWebhookHandler implements WebhookHandlerInterface
{
    public function handle(WebhookData $data): void
    {
        $collectionId = $data->payload['id'] ?? null;

        if (!$data->storeId || !$collectionId) {
            return;
        }

        $gid = str_starts_with((string) $collectionId, 'gid://')
            ? (string) $collectionId
            : 'gid://shopify/Collection/' . (string) $collectionId;

        $collection = Collection::query()
            ->where('store_id', $data->storeId)
            ->where(function ($q) use ($gid, $collectionId) {
                $q->where('shopify_collection_id', $gid)
                    ->orWhere('shopify_collection_id', (string) $collectionId);
            })
            ->first();

        if (!$collection) {
            return;
        }

        $collection->update(['is_active' => false]);
        $collection->delete();
    }
}

