<?php

namespace App\Modules\CMS\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ArticleDetailResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'store_id' => $this->store_id,
            'blog_id' => $this->blog_id,
            'blog' => $this->whenLoaded('blog', fn () => [
                'id' => $this->blog?->id,
                'title' => $this->blog?->title,
                'handle' => $this->blog?->handle,
                'shopify_blog_id' => $this->blog?->shopify_blog_id,
            ]),
            'shopify_article_id' => $this->shopify_article_id,
            'title' => $this->title,
            'handle' => $this->handle,
            'author_name' => $this->author_name,
            'body' => $this->body,
            'summary' => $this->summary,
            'tags' => $this->tags,
            'comments_count' => $this->comments_count,
            'comments' => $this->whenLoaded('comments', fn () => $this->comments->map(fn ($comment) => [
                'id' => $comment->id,
                'store_id' => $comment->store_id,
                'article_id' => $comment->article_id,
                'shopify_comment_id' => $comment->shopify_comment_id,
                'author' => $comment->author,
                'email' => $comment->email,
                'ip' => $comment->ip,
                'status' => $comment->status,
                'body' => $comment->body,
                'published_at' => $comment->published_at,
                'shopify_created_at' => $comment->shopify_created_at,
                'shopify_updated_at' => $comment->shopify_updated_at,
                'created_at' => $comment->created_at,
                'updated_at' => $comment->updated_at,
            ])->values()),
            'template_suffix' => $this->template_suffix,
            'seo_title' => $this->seo_title,
            'seo_description' => $this->seo_description,
            'is_published' => (bool) $this->is_published,
            'published_at' => $this->published_at,
            'shopify_created_at' => $this->shopify_created_at,
            'shopify_updated_at' => $this->shopify_updated_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
