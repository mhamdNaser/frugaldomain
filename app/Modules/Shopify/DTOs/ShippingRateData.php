<?php

namespace App\Modules\Shopify\DTOs;

class ShippingRateData
{
    public function __construct(
        public readonly string $shopifyRateId,
        public readonly string $shopifyMethodId,
        public readonly ?string $shopifyZoneId,
        public readonly ?string $name,
        public readonly ?float $amount,
        public readonly ?string $currency,
        public readonly array $rawPayload,
    ) {}
}

