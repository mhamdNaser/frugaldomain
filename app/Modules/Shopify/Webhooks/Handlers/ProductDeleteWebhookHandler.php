<?php

namespace App\Modules\Shopify\Webhooks\Handlers;

use App\Modules\Shopify\Webhooks\DTOs\WebhookData;
use App\Modules\Shopify\Webhooks\Jobs\DeleteSingleProductJob;

class ProductDeleteWebhookHandler implements WebhookHandlerInterface
{
    public function handle(WebhookData $data): void
    {
        $productId = $data->payload['id'] ?? null;

        if (!$data->storeId || !$productId) {
            return;
        }

        DeleteSingleProductJob::dispatch(
            storeId: $data->storeId,
            shopifyProductId: (string) $productId,
            webhookExternalId: $data->externalId,
        )->onQueue('shopify-sync');
    }
}

