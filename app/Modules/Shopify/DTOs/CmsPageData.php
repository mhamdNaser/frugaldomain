<?php

namespace App\Modules\Shopify\DTOs;

class CmsPageData
{
    public function __construct(
        public readonly string $shopifyPageId,
        public readonly ?string $handle,
        public readonly string $title,
        public readonly ?string $author,
        public readonly ?string $body,
        public readonly ?string $seoTitle,
        public readonly ?string $seoDescription,
        public readonly ?string $templateSuffix,
        public readonly bool $isPublished,
        public readonly ?string $publishedAt,
        public readonly ?string $shopifyCreatedAt,
        public readonly ?string $shopifyUpdatedAt,
        public readonly array $rawPayload,
    ) {}
}
