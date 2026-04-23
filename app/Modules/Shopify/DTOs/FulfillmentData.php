<?php

namespace App\Modules\Shopify\DTOs;

class FulfillmentData
{
    public function __construct(
        public readonly string $shopifyFulfillmentId,
        public readonly ?FulfillmentServiceData $service,
        public readonly ?string $name,
        public readonly ?string $status,
        public readonly ?string $displayStatus,
        public readonly ?string $shopifyCreatedAt,
        public readonly ?string $shopifyUpdatedAt,
        public readonly array $rawPayload,
        public readonly array $items = [],
        public readonly array $tracking = [],
    ) {}

    public function primaryTracking(): ?FulfillmentTrackingData
    {
        return $this->tracking[0] ?? null;
    }
}
