<?php

namespace App\Modules\Shopify\DTOs;

class CollectionData
{
    public function __construct(
        public readonly string $shopifyCollectionId,
        public readonly string $title,
        public readonly string $handle,
        public readonly ?string $description,
        public readonly ?string $imageUrl,
        public readonly ?string $imageAlt,
        public readonly string $type,
        public readonly ?string $seoTitle,
        public readonly ?string $seoDescription,
        public readonly array $rawPayload,
    ) {}
}
