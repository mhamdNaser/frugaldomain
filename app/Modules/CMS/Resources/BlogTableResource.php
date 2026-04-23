<?php

namespace App\Modules\CMS\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class BlogTableResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'store_id' => $this->store_id,
            'shopify_blog_id' => $this->shopify_blog_id,
            'title' => $this->title,
            'handle' => $this->handle,
            'comment_policy' => $this->comment_policy,
            'tags' => $this->tags,
            'articles_count' => $this->articles_count ?? 0,
            'is_published' => (bool) $this->is_published,
            'published_at' => $this->published_at,
            'shopify_created_at' => $this->shopify_created_at,
            'shopify_updated_at' => $this->shopify_updated_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
