<?php

namespace App\Modules\Shopify\DTOs;

class TaxLineData
{
    public function __construct(
        public readonly ?string $shopifyTaxLineId,
        public readonly ?string $title,
        public readonly float $rate,
        public readonly float $ratePercentage,
        public readonly float $price,
        public readonly ?string $currency,
        public readonly ?bool $channelLiable,
        public readonly bool $isShipping,
        public readonly array $rawPayload,
    ) {}
}
