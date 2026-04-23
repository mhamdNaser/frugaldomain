<?php

namespace App\Modules\Shopify\DTOs;

class SellingPlanSubscriptionData
{
    public function __construct(
        public readonly string $shopifySubscriptionContractId,
        public readonly ?string $shopifyCustomerId,
        public readonly ?string $status,
        public readonly ?string $currency,
        public readonly ?float $nextBillingAmount,
        public readonly ?string $nextBillingDate,
        public readonly array $rawPayload = [],
    ) {}
}

