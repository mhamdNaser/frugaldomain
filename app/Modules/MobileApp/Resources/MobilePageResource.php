<?php

namespace App\Modules\MobileApp\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class MobilePageResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'handle' => $this->handle,
            'author' => $this->author,
            'body' => $this->body,
            'is_published' => (bool) $this->is_published,
            'published_at' => $this->published_at,
            'seo_title' => $this->seo_title,
            'seo_description' => $this->seo_description,
        ];
    }
}
