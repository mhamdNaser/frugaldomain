<?php

namespace App\Modules\Shopify\DTOs;

class DraftOrderData
{
    public function __construct(
        public readonly string $shopifyDraftOrderId,
        public readonly ?string $shopifyCustomerId,
        public readonly ?string $name,
        public readonly string $status,
        public readonly ?string $invoiceUrl,
        public readonly float $subtotal,
        public readonly float $tax,
        public readonly float $total,
        public readonly ?string $currency,
        public readonly ?string $completedAt,
        public readonly ?string $shopifyCreatedAt,
        public readonly ?string $shopifyUpdatedAt,
        public readonly array $rawPayload,
        public readonly array $items = [],
    ) {}
}
