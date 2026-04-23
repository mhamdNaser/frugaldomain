<?php

namespace App\Modules\Shopify\DTOs;

class ShippingMethodData
{
    public function __construct(
        public readonly string $shopifyMethodId,
        public readonly ?string $shopifyZoneId,
        public readonly ?string $name,
        public readonly ?string $description,
        public readonly bool $active,
        public readonly ?string $methodType,
        public readonly array $conditions,
        public readonly array $rawPayload,
    ) {}
}

