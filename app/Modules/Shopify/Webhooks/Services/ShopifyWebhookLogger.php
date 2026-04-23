<?php

namespace App\Modules\Shopify\Webhooks\Services;

use App\Modules\Core\Models\WebhookLog;
use App\Modules\Shopify\Webhooks\DTOs\WebhookData;

class ShopifyWebhookLogger
{
    public function log(WebhookData $data): WebhookLog
    {
        if ($data->externalId) {
            return WebhookLog::query()->firstOrCreate(
                [
                    'store_id' => $data->storeId,
                    'provider' => $data->provider,
                    'external_id' => $data->externalId,
                ],
                [
                    'topic' => $data->topic,
                    'payload' => $data->rawBody,
                    'status' => 'pending',
                    'attempts' => 0,
                    'received_at' => now(),
                ]
            );
        }

        return WebhookLog::query()->create([
            'store_id' => $data->storeId,
            'provider' => $data->provider,
            'topic' => $data->topic,
            'external_id' => null,
            'payload' => $data->rawBody,
            'status' => 'pending',
            'attempts' => 0,
            'received_at' => now(),
        ]);
    }
}

