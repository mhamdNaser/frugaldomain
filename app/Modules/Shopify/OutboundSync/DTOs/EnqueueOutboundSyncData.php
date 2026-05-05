<?php

namespace App\Modules\Shopify\OutboundSync\DTOs;

class EnqueueOutboundSyncData
{
    /**
     * @param array<string, mixed> $payload
     */
    public function __construct(
        public readonly string $storeId,
        public readonly string $entityType,
        public readonly string $entityId,
        public readonly string $action,
        public readonly string $handler,
        public readonly array $payload,
        public readonly ?string $idempotencyKey = null,
        public readonly ?string $correlationId = null,
        public readonly int $priority = 5,
        public readonly int $maxAttempts = 5,
    ) {}
}

