<?php

namespace App\Modules\Shopify\DTOs;

class ArticleData
{
    public function __construct(
        public readonly string $shopifyArticleId,
        public readonly ?string $handle,
        public readonly string $title,
        public readonly ?string $authorName,
        public readonly ?string $body,
        public readonly ?string $summary,
        public readonly array $tags,
        public readonly ?string $seoTitle,
        public readonly ?string $seoDescription,
        public readonly ?string $templateSuffix,
        public readonly bool $isPublished,
        public readonly ?string $publishedAt,
        public readonly int $commentsCount,
        public readonly ?string $shopifyCreatedAt,
        public readonly ?string $shopifyUpdatedAt,
        public readonly array $rawPayload,
        public readonly array $comments = [],
    ) {}
}
