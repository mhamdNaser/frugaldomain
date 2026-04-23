<?php

namespace App\Modules\CMS\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PageTableResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'store_id' => $this->store_id,
            'shopify_page_id' => $this->shopify_page_id,
            'title' => $this->title,
            'seo_title' => $this->seo_title,
            'seo_description' => $this->seo_description,
            'handle' => $this->handle,
            'author' => $this->author,
            'body' => $this->body,
            'is_published' => (bool) $this->is_published,
            'published_at' => $this->published_at,
            'shopify_created_at' => $this->shopify_created_at,
            'shopify_updated_at' => $this->shopify_updated_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
