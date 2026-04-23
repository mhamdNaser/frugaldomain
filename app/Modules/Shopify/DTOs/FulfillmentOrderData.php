<?php

namespace App\Modules\Shopify\DTOs;

class FulfillmentOrderData
{
    public function __construct(
        public readonly string $shopifyFulfillmentOrderId,
        public readonly ?FulfillmentServiceData $service,
        public readonly ?string $shopifyAssignedLocationId,
        public readonly ?string $assignedLocationName,
        public readonly ?string $status,
        public readonly ?string $requestStatus,
        public readonly ?string $fulfillAt,
        public readonly ?string $fulfillBy,
        public readonly ?array $destination,
        public readonly ?array $deliveryMethod,
        public readonly ?string $shopifyCreatedAt,
        public readonly ?string $shopifyUpdatedAt,
        public readonly array $rawPayload,
        public readonly array $items = [],
    ) {}
}
