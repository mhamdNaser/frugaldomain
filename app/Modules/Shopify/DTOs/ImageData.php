<?php

namespace App\Modules\Shopify\DTOs;

class ImageData
{
    public function __construct(
        public readonly ?string $shopifyImageId,
        public readonly string $url,
        public readonly ?string $alt,
        public readonly int $position,
        public readonly ?int $width,
        public readonly ?int $height,
    ) {}
}
