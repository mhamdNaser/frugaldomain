<?php

namespace App\Modules\CMS\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class MetaDefinitionsResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'store_id' => $this->store_id,
            'shopify_metaobject_definition_id' => $this->shopify_metaobject_definition_id,
            'type' => $this->type,
            'name' => $this->name,
            'display_name_key' => $this->display_name_key,
            'access' => $this->access,
            'capabilities' => $this->capabilities,
            'fields_count' => (int) ($this->fields_count ?? 0),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
