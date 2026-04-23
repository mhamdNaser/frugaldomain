<?php

namespace App\Modules\CMS\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class MenuItemTableResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'store_id' => $this->store_id,
            'menu_id' => $this->menu_id,
            'parent_id' => $this->parent_id,
            'shopify_menu_item_id' => $this->shopify_menu_item_id,
            'resource_id' => $this->resource_id,
            'title' => $this->title,
            'type' => $this->type,
            'url' => $this->url,
            'tags' => $this->tags,
            'position' => $this->position,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}

