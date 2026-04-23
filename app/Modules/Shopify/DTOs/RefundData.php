<?php

namespace App\Modules\Shopify\DTOs;

class RefundData
{
    public function __construct(
        public readonly string $shopifyRefundId,
        public readonly ?string $note,
        public readonly float $total,
        public readonly ?string $currency,
        public readonly ?string $processedAt,
        public readonly ?string $shopifyCreatedAt,
        public readonly ?string $shopifyUpdatedAt,
        public readonly array $rawPayload,
        public readonly array $items = [],
        public readonly array $transactions = [],
    ) {}
}
