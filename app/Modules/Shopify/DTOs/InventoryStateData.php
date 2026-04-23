<?php

namespace App\Modules\Shopify\DTOs;

class InventoryStateData
{
    public function __construct(
        public readonly string $inventoryItemId,
        public readonly ?string $shopifyLocationId,
        public readonly int $available,
        public readonly int $committed,
        public readonly int $incoming,
        public readonly int $reserved,
        public readonly int $onHand,
        public readonly ?string $updatedAt,
        public readonly array $rawPayload = [],
    ) {}
}

