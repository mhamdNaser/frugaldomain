<?php

namespace App\Modules\Shopify\DTOs;

class ProductVariantData
{
    public function __construct(
        public readonly string $shopifyVariantId,
        public readonly ?string $title,
        public readonly ?string $sku,
        public readonly ?string $barcode,
        public readonly ?float $price,
        public readonly ?float $compareAtPrice,
        public readonly bool $isDefault,
        public readonly bool $availableForSale,
        public readonly bool $taxable,
        public readonly ?int $position,
        public readonly int $inventoryQuantity,
        public readonly ?string $shopifyCreatedAt,
        public readonly ?string $shopifyUpdatedAt,
        public readonly ?array $rawPayload,
        public readonly ?ImageData $image,
        public readonly ?InventoryData $inventory,

        public readonly ?array $selectedOptions = null,
    ) {}
}
