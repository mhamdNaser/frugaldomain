<?php

namespace App\Modules\Shopify\DTOs;

class ProductAdvancedMediaData
{
    public function __construct(
        public readonly string $shopifyProductId,
        public readonly string $shopifyMediaId,
        public readonly ?string $contentType,
        public readonly ?string $status,
        public readonly int $position,
        public readonly ?string $alt,
        public readonly ?string $url,
        public readonly ?string $previewUrl,
        public readonly ?string $mimeType,
        public readonly ?int $width,
        public readonly ?int $height,
        public readonly array $rawPayload = [],
    ) {}
}

