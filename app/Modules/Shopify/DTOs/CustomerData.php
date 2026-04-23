<?php

namespace App\Modules\Shopify\DTOs;

class CustomerData
{
    public function __construct(
        public readonly string $shopifyCustomerId,
        public readonly ?string $firstName,
        public readonly ?string $lastName,
        public readonly ?string $displayName,
        public readonly ?string $email,
        public readonly ?string $phone,
        public readonly ?string $status,
        public readonly ?string $state,
        public readonly array $tags,
        public readonly ?string $note,
        public readonly bool $verifiedEmail,
        public readonly bool $taxExempt,
        public readonly int $ordersCount,
        public readonly float $totalSpent,
        public readonly ?string $currency,
        public readonly ?string $defaultAddressId,
        public readonly ?string $shopifyCreatedAt,
        public readonly ?string $shopifyUpdatedAt,
        public readonly array $rawPayload,
        public readonly array $addresses = [],
    ) {}
}
