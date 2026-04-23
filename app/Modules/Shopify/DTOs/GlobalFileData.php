<?php

namespace App\Modules\Shopify\DTOs;

class GlobalFileData
{
    public function __construct(
        public readonly string $shopifyFileId,
        public readonly string $url,
        public readonly ?string $mimeType,
        public readonly ?int $width,
        public readonly ?int $height,
        public readonly ?string $alt,
        public readonly string $type,
        public readonly array $rawPayload,
    ) {}
}
