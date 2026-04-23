<?php

namespace App\Modules\Shopify\DTOs;

class MenuItemData
{
    public function __construct(
        public readonly string $shopifyMenuItemId,
        public readonly ?string $resourceId,
        public readonly string $title,
        public readonly ?string $type,
        public readonly ?string $url,
        public readonly array $tags,
        public readonly int $position,
        public readonly array $rawPayload,
        public readonly array $items = [],
    ) {}
}
