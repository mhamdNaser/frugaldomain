<?php

namespace App\Modules\Shopify\DTOs;

class FulfillmentServiceData
{
    public function __construct(
        public readonly string $shopifyFulfillmentServiceId,
        public readonly ?string $name,
        public readonly ?string $email,
        public readonly ?string $serviceName,
        public readonly ?string $type,
        public readonly bool $callbackUrl,
        public readonly array $rawPayload,
    ) {}
}
