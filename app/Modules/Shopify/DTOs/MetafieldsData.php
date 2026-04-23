<?php

namespace App\Modules\Shopify\DTOs;

class MetafieldsData
{
    public function __construct(
        public readonly ?string $shopifyMetafieldId,
        public readonly ?string $namespace,
        public readonly ?string $key,
        public readonly ?string $type,
        public readonly mixed $value,

        // 🔥 جديد
        public readonly ?string $referenceId = null,
        public readonly array $referenceIds = [],
        public readonly array $metaobjects = [],
    ) {}
}
