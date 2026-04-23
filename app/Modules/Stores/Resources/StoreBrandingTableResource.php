<?php

namespace App\Modules\Stores\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class StoreBrandingTableResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'store_id' => $this->store_id,
            'logo_url' => $this->logo_url,
            'splash_image_url' => $this->splash_image_url,
            'favicon_url' => $this->favicon_url,
            'primary_color' => $this->primary_color,
            'secondary_color' => $this->secondary_color,
            'dark_primary_color' => $this->dark_primary_color,
            'dark_secondary_color' => $this->dark_secondary_color,
            'font_family' => $this->font_family,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}

