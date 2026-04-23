<?php

namespace App\Modules\Shopify\DTOs;

class MenuData
{
    public function __construct(
        public readonly string $shopifyMenuId,
        public readonly ?string $handle,
        public readonly string $title,
        public readonly int $itemsCount,
        public readonly array $rawPayload,
        public readonly array $items = [],
    ) {}
}
