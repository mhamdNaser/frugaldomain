<?php

namespace App\Modules\Shopify\DTOs;

class OrderDutyBreakdownData
{
    public function __construct(
        public readonly string $shopifyOrderId,
        public readonly float $orderDutyTotal,
        public readonly ?string $currency,
        public readonly array $lineItemDuties = [],
        public readonly array $rawPayload = [],
    ) {}
}

