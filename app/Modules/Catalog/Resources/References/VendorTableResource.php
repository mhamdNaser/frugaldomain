<?php

namespace App\Modules\Catalog\Resources\References;

use Illuminate\Http\Resources\Json\JsonResource;

class VendorTableResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'store_id' => $this->store_id,
            'shopify_vendor_id' => $this->shopify_vendor_id,
            'name' => $this->name,
            'slug' => $this->slug,
            'email' => $this->email,
            'contact_phone' => $this->contact_phone,
            'description' => $this->description,
            'is_active' => (bool) $this->is_active,
            'meta_title' => $this->meta_title,
            'meta_description' => $this->meta_description,
            'products_count' => $this->products_count ?? 0,
            'tags_count' => $this->tags_count ?? 0,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
