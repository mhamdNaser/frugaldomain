<?php

namespace App\Modules\Shopify\DTOs;

class SellingPlanGroupData
{
    public function __construct(
        public readonly string $shopifySellingPlanGroupId,
        public readonly ?string $name,
        public readonly ?string $appId,
        public readonly array $options = [],
        public readonly ?string $summary,
        public readonly array $productIds = [],
        public readonly array $plans = [],
        public readonly array $rawPayload = [],
    ) {}
}

