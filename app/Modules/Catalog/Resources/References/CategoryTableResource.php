<?php

namespace App\Modules\Catalog\Resources\References;

use Illuminate\Http\Resources\Json\JsonResource;

class CategoryTableResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'store_id' => $this->store_id,
            'shopify_category_id' => $this->shopify_category_id,
            'name' => $this->name,
            'slug' => $this->slug,
            'products_count' => $this->products_count ?? 0,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
