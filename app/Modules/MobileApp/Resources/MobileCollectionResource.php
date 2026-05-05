<?php

namespace App\Modules\MobileApp\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class MobileCollectionResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'handle' => $this->handle,
            'description' => $this->description,
            'image_url' => $this->image_url,
            'image_alt' => $this->image_alt,
            'type' => $this->type,
            'is_active' => (bool) $this->is_active,
            'products' => $this->whenLoaded('products', fn () => MobileProductResource::collection($this->products)),
        ];
    }
}
