<?php

namespace App\Modules\Shopify\DTOs;

class FulfillmentOrderItemData
{
    public function __construct(
        public readonly string $shopifyFulfillmentOrderLineItemId,
        public readonly ?string $shopifyLineItemId,
        public readonly int $totalQuantity,
        public readonly int $remainingQuantity,
        public readonly array $rawPayload,
    ) {}
}
