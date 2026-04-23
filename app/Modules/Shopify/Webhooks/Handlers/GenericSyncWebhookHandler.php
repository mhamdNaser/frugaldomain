<?php

namespace App\Modules\Shopify\Webhooks\Handlers;

use App\Modules\Shopify\Webhooks\DTOs\WebhookData;
use App\Modules\Shopify\Webhooks\Jobs\DispatchSyncTypeFromWebhookJob;
use App\Modules\Shopify\Webhooks\Services\ShopifyWebhookTopicSyncResolver;

class GenericSyncWebhookHandler implements WebhookHandlerInterface
{
    public function __construct(
        private readonly ShopifyWebhookTopicSyncResolver $resolver,
    ) {}

    public function handle(WebhookData $data): void
    {
        if (!$data->storeId) {
            return;
        }

        $types = $this->resolver->resolve($data->topic);

        foreach (array_values(array_unique($types)) as $syncType) {
            DispatchSyncTypeFromWebhookJob::dispatch(
                storeId: $data->storeId,
                syncType: $syncType,
                webhookExternalId: $data->externalId,
                topic: $data->topic,
            )->onQueue('shopify-sync');
        }
    }
}

