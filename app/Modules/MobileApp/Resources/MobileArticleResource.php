<?php

namespace App\Modules\MobileApp\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class MobileArticleResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'blog_id' => $this->blog_id,
            'title' => $this->title,
            'handle' => $this->handle,
            'summary' => $this->summary,
            'body' => $this->body,
            'author_name' => $this->author_name,
            'tags' => $this->tags,
            'comments_count' => $this->comments_count,
            'is_published' => (bool) $this->is_published,
            'published_at' => $this->published_at,
        ];
    }
}
