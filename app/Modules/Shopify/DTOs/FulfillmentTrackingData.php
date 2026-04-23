<?php

namespace App\Modules\Shopify\DTOs;

class FulfillmentTrackingData
{
    public function __construct(
        public readonly ?string $company,
        public readonly ?string $number,
        public readonly ?string $url,
        public readonly array $rawPayload,
    ) {}
}
