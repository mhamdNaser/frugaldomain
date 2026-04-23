<?php

namespace App\Modules\Catalog\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ProductMediaResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'store_id' => $this->store_id,
            'product_id' => $this->product_id,
            'shopify_product_id' => $this->shopify_product_id,
            'shopify_media_id' => $this->shopify_media_id,
            'media_content_type' => $this->media_content_type,
            'status' => $this->status,
            'position' => $this->position,
            'alt' => $this->alt,
            'url' => $this->url,
            'preview_url' => $this->preview_url,
            'mime_type' => $this->mime_type,
            'width' => $this->width,
            'height' => $this->height,
            'raw_payload' => $this->raw_payload,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
