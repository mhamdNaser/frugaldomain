<?php

namespace App\Modules\Shopify\DTOs;

class MetaobjectDefinitionData
{
    public function __construct(
        public readonly string $shopifyMetaobjectDefinitionId,
        public readonly ?string $type,
        public readonly ?string $name,
        public readonly ?string $displayNameKey,
        public readonly array $access = [],
        public readonly array $capabilities = [],
        public readonly array $fields = [],
        public readonly array $rawPayload = [],
    ) {}
}

