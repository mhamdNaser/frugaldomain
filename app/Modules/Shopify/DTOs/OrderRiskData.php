<?php

namespace App\Modules\Shopify\DTOs;

class OrderRiskData
{
    public function __construct(
        public readonly string $shopifyOrderId,
        public readonly ?string $recommendation,
        public readonly ?string $riskLevel,
        public readonly array $assessments = [],
        public readonly array $channel = [],
        public readonly array $rawPayload = [],
    ) {}
}

