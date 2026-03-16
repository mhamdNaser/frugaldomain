<?php

namespace App\Modules\Icon\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class IconResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'category_name' => $this->category ? $this->category->name : null,
            'category_id' => $this->category_id,
            'is_premium' => $this->is_premium,
            'is_active' => $this->is_active,
            'icon_text' => optional($this->files->where('file_type', 'svg')->first())->file_path,
            'file_png' => optional($this->files->where('file_type', 'png')->first())->file_path,
            'file_svg' => optional($this->files->where('file_type', 'svg')->first())->file_path,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}

