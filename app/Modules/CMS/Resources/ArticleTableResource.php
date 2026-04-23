<?php

namespace App\Modules\CMS\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ArticleTableResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'store_id' => $this->store_id,
            'blog_id' => $this->blog_id,
            'blog' => $this->blog?->title,
            'shopify_article_id' => $this->shopify_article_id,
            'title' => $this->title,
            'handle' => $this->handle,
            'author_name' => $this->author_name,
            'summary' => $this->summary,
            'tags' => $this->tags,
            'comments_count' => $this->comments_count,
            'is_published' => (bool) $this->is_published,
            'published_at' => $this->published_at,
            'shopify_created_at' => $this->shopify_created_at,
            'shopify_updated_at' => $this->shopify_updated_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
