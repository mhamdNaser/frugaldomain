<?php

namespace App\Modules\CMS\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class MetaDefinitionsFiledsResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'metaobject_definition_id' => $this->metaobject_definition_id,
            'field_key' => $this->field_key,
            'name' => $this->name,
            'type' => $this->type,
            'required' => (bool) $this->required,
            'validations' => $this->validations,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
