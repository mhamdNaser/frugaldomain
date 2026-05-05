<?php

namespace App\Modules\MobileApp\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class MobileBlogResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'handle' => $this->handle,
            'is_published' => (bool) $this->is_published,
            'published_at' => $this->published_at,
            'tags' => $this->tags,
            'seo_title' => $this->seo_title,
            'seo_description' => $this->seo_description,
        ];
    }
}
