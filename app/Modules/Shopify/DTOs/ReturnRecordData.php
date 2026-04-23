<?php

namespace App\Modules\Shopify\DTOs;

class ReturnRecordData
{
    public function __construct(
        public readonly string $shopifyReturnId,
        public readonly ?string $status,
        public readonly ?string $name,
        public readonly ?string $requestedAt,
        public readonly ?string $openedAt,
        public readonly ?string $closedAt,
        public readonly array $returnItems = [],
        public readonly array $exchangeItems = [],
        public readonly array $reverseFulfillments = [],
        public readonly array $rawPayload = [],
    ) {}
}

