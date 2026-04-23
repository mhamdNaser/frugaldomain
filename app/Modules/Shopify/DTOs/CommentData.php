<?php

namespace App\Modules\Shopify\DTOs;

class CommentData
{
    public function __construct(
        public readonly string $shopifyCommentId,
        public readonly ?string $author,
        public readonly ?string $email,
        public readonly ?string $ip,
        public readonly ?string $status,
        public readonly ?string $body,
        public readonly ?string $publishedAt,
        public readonly ?string $shopifyCreatedAt,
        public readonly ?string $shopifyUpdatedAt,
        public readonly array $rawPayload,
    ) {}
}
