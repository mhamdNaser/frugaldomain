<?php

namespace App\Modules\Shopify\Webhooks\DTOs;

class WebhookData
{
    /**
     * @param array<string, mixed>|null $payload
     * @param array<string, mixed> $headers
     */
    public function __construct(
        public readonly ?string $storeId,
        public readonly string $provider,
        public readonly string $topic,
        public readonly ?string $externalId,
        public readonly ?string $shopDomain,
        public readonly ?array $payload,
        public readonly string $rawBody,
        public readonly array $headers,
        public readonly ?string $hmacHeader,
    ) {}
}

