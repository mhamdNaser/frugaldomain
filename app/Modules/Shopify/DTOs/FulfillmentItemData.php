<?php

namespace App\Modules\Shopify\DTOs;

class FulfillmentItemData
{
    public function __construct(
        public readonly ?string $shopifyLineItemId,
        public readonly int $quantity,
        public readonly array $rawPayload,
    ) {}
}
