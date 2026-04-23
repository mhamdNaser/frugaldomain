<?php

namespace App\Modules\Shopify\DTOs;

class DiscountData
{
    public function __construct(
        public readonly string $shopifyDiscountId,
        public readonly string $discountType,
        public readonly string $method,
        public readonly ?string $title,
        public readonly string $status,
        public readonly ?string $summary,
        public readonly ?string $shortSummary,
        public readonly ?int $usageLimit,
        public readonly int $usageCount,
        public readonly float $totalSales,
        public readonly ?string $currency,
        public readonly ?string $startsAt,
        public readonly ?string $endsAt,
        public readonly ?string $shopifyUpdatedAt,
        public readonly array $rawPayload,
        public readonly array $codes = [],
    ) {}
}
