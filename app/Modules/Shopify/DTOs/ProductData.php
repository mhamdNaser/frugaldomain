<?php

namespace App\Modules\Shopify\DTOs;

class ProductData
{
    public function __construct(
        public readonly string $shopifyProductId,
        public readonly string $title,
        public readonly string $slug,
        public readonly ?string $description,
        public readonly string $handle,
        public readonly string $status,
        public readonly ?string $seoTitle,
        public readonly ?string $seoDescription,
        public readonly ?string $publishedAt,
        public readonly ?string $shopifyCreatedAt,
        public readonly ?string $shopifyUpdatedAt,
        public readonly ?string $vendor,
        public readonly ?string $productType,
        public readonly ?string $categoryId,
        public readonly ?string $categoryName,
        public readonly ?bool $isGiftCard,
        public readonly ?bool $hasOnlyDefaultVariant,
        public readonly ?array $rawPayload,
        public readonly ?array $featuredImage,
        public readonly ?array $tags = [],
        public readonly ?array $metafields = [],
        public readonly array $options = [],
        public readonly array $images = [],
        public readonly array $variants = [],
        public readonly array $collections = []
    ) {}

    public function variants(): array
    {
        return $this->variants;
    }
}
