<?php

namespace App\Modules\CMS\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class MetafieldTableResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'store_id' => $this->store_id,
            'shopify_metafield_id' => $this->shopify_metafield_id,
            'metafieldable_type' => $this->metafieldable_type,
            'metafieldable_label' => $this->metafieldable_type ? class_basename($this->metafieldable_type) : null,
            'metafieldable_id' => $this->metafieldable_id,
            'namespace' => $this->namespace,
            'key' => $this->key,
            'type' => $this->type,
            'value' => $this->value,
            'metaobjects_count' => $this->metaobjects_count ?? 0,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
