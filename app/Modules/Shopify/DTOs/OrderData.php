<?php

namespace App\Modules\Shopify\DTOs;

class OrderData
{
    public function __construct(
        public readonly string $shopifyOrderId,
        public readonly ?string $shopifyCustomerId,
        public readonly ?string $email,
        public readonly ?string $orderNumber,
        public readonly string $status,
        public readonly string $paymentStatus,
        public readonly string $fulfillmentStatus,
        public readonly float $subtotal,
        public readonly float $tax,
        public readonly float $shipping,
        public readonly float $discount,
        public readonly float $total,
        public readonly ?string $currency,
        public readonly ?string $placedAt,
        public readonly ?string $shopifyCreatedAt,
        public readonly ?string $shopifyUpdatedAt,
        public readonly array $rawPayload,
        public readonly array $items = [],
    ) {}
}
