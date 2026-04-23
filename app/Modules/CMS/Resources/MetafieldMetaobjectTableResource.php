<?php

namespace App\Modules\CMS\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class MetafieldMetaobjectTableResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => "{$this->metafield_id}-{$this->metaobject_id}",
            'metafield_id' => $this->metafield_id,
            'metaobject_id' => $this->metaobject_id,
            'metafield_namespace' => $this->metafield?->namespace,
            'metafield_key' => $this->metafield?->key,
            'metafield_type' => $this->metafield?->type,
            'metaobject_type' => $this->metaobject?->type,
            'shopify_metaobject_id' => $this->metaobject?->shopify_metaobject_id,
        ];
    }
}
