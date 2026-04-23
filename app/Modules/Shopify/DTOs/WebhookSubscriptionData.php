<?php

namespace App\Modules\Shopify\DTOs;

class WebhookSubscriptionData
{
    public function __construct(
        public readonly string $shopifyWebhookId,
        public readonly string $topic,
        public readonly string $event,
        public readonly ?string $callbackUrl,
        public readonly bool $isActive,
        public readonly string $provider,
        public readonly ?string $endpointType,
        public readonly ?string $format,
        public readonly array $rawPayload,
    ) {}
}

