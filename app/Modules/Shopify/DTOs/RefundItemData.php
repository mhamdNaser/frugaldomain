<?php

namespace App\Modules\Shopify\DTOs;

class RefundItemData
{
    public function __construct(
        public readonly ?string $shopifyRefundLineItemId,
        public readonly ?string $shopifyLineItemId,
        public readonly int $quantity,
        public readonly string $restockType,
        public readonly bool $restocked,
        public readonly float $subtotal,
        public readonly float $tax,
        public readonly float $total,
        public readonly ?string $currency,
        public readonly array $rawPayload,
    ) {}
}
