<?php

namespace App\Modules\Catalog\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ProductFileResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'url' => $this->url,
            'path' => $this->path,
            'disk' => $this->disk,
            'type' => $this->type,
            'role' => $this->role,
            'mime_type' => $this->mime_type,
            'width' => $this->width,
            'height' => $this->height,
            'alt' => $this->altText,
            'position' => $this->position,
            'shopify_id' => $this->shopify_id,
            'meta' => $this->meta,
        ];
    }
}
