<?php

namespace App\Modules\Shopify\DTOs;

class ShippingZoneData
{
    public function __construct(
        public readonly string $shopifyZoneId,
        public readonly ?string $shopifyProfileId,
        public readonly ?string $name,
        public readonly array $countries,
        public readonly array $rawPayload,
    ) {}
}

