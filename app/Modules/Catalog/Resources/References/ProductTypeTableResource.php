<?php

namespace App\Modules\Catalog\Resources\References;

use Illuminate\Http\Resources\Json\JsonResource;

class ProductTypeTableResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'store_id' => $this->store_id,
            'shopify_product_type_id' => $this->shopify_product_type_id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'products_count' => $this->products_count ?? 0,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
