<?php

namespace App\Modules\Shopify\DTOs;

class OrderItemData
{
    public function __construct(
        public readonly ?string $shopifyLineItemId,
        public readonly ?string $shopifyProductId,
        public readonly ?string $shopifyVariantId,
        public readonly string $productTitle,
        public readonly ?string $variantTitle,
        public readonly ?string $sku,
        public readonly int $quantity,
        public readonly float $unitPrice,
        public readonly float $totalPrice,
        public readonly array $rawPayload,
    ) {}
}
