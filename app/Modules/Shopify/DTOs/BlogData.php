<?php

namespace App\Modules\Shopify\DTOs;

class BlogData
{
    public function __construct(
        public readonly string $shopifyBlogId,
        public readonly ?string $handle,
        public readonly string $title,
        public readonly ?string $commentPolicy,
        public readonly array $tags,
        public readonly ?string $seoTitle,
        public readonly ?string $seoDescription,
        public readonly ?string $templateSuffix,
        public readonly bool $isPublished,
        public readonly ?string $publishedAt,
        public readonly ?string $shopifyCreatedAt,
        public readonly ?string $shopifyUpdatedAt,
        public readonly array $rawPayload,
        public readonly array $articles = [],
    ) {}
}
