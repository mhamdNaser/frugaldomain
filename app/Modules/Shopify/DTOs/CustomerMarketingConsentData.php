<?php

namespace App\Modules\Shopify\DTOs;

class CustomerMarketingConsentData
{
    public function __construct(
        public readonly string $shopifyCustomerId,
        public readonly ?string $emailMarketingState,
        public readonly ?string $emailMarketingOptInLevel,
        public readonly ?string $emailConsentUpdatedAt,
        public readonly ?string $smsMarketingState,
        public readonly ?string $smsMarketingOptInLevel,
        public readonly ?string $smsConsentUpdatedAt,
        public readonly ?string $sourceLocationId,
        public readonly array $rawPayload = [],
    ) {}
}

