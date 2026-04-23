<?php

namespace App\Modules\Shopify\DTOs;

class DiscountCodeData
{
    public function __construct(
        public readonly ?string $shopifyDiscountCodeId,
        public readonly ?string $code,
        public readonly int $usageCount,
        public readonly array $rawPayload,
    ) {}
}
