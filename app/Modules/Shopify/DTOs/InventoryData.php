<?php

namespace App\Modules\Shopify\DTOs;

class InventoryData
{
    public function __construct(
        public readonly string $inventoryItemId,
        public readonly bool $tracked,
        public readonly bool $requiresShipping,
        public readonly ?float $weight,
        public readonly string $weightUnit,
        public readonly array $locations,
        public readonly int $quantity,
        public readonly ?array $rawPayload = null,
    ) {}
}
