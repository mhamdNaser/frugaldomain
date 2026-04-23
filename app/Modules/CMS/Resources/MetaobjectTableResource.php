<?php

namespace App\Modules\CMS\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class MetaobjectTableResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'store_id' => $this->store_id,
            'shopify_metaobject_id' => $this->shopify_metaobject_id,
            'type' => $this->type,
            'fields' => $this->fields,
            'fields_count' => is_array($this->fields) ? count($this->fields) : 0,
            'metafields_count' => $this->metafields_count ?? 0,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
