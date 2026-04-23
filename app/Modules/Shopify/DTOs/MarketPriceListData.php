<?php

namespace App\Modules\Shopify\DTOs;

class MarketPriceListData
{
    public function __construct(
        public readonly string $shopifyMarketId,
        public readonly ?string $marketName,
        public readonly ?string $marketHandle,
        public readonly bool $enabled,
        public readonly bool $isPrimary,
        public readonly ?string $currency,
        public readonly array $priceLists = [],
        public readonly array $rawPayload = [],
    ) {}
}

