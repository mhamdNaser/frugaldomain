<?php

namespace App\Modules\Shopify\DTOs;

class StoreInstallData
{
    public function __construct(
        public readonly string $shop,
        public readonly ?string $state,
        public readonly ?string $scopes,
        public readonly ?string $shopifyStoreId,
        public readonly ?array $rawPayload = null,
    ) {}
}

