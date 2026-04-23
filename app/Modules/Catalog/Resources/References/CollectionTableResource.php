<?php

namespace App\Modules\Catalog\Resources\References;

use Illuminate\Http\Resources\Json\JsonResource;

class CollectionTableResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'store_id' => $this->store_id,
            'shopify_collection_id' => $this->shopify_collection_id,
            'title' => $this->title,
            'handle' => $this->handle,
            'description' => $this->description,
            'image_url' => $this->image_url,
            'image_alt' => $this->image_alt,
            'type' => $this->type,
            'is_active' => (bool) $this->is_active,
            'sort_order' => $this->sort_order,
            'seo_title' => $this->seo_title,
            'seo_description' => $this->seo_description,
            'products_count' => $this->products_count ?? 0,
            'tags_count' => $this->tags_count ?? 0,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'deleted_at' => $this->deleted_at,
        ];
    }
}
