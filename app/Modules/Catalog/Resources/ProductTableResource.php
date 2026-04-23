<?php

namespace App\Modules\Catalog\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ProductTableResource extends JsonResource
{
    public function toArray($request): array
    {
        $featuredImage = $this->featuredImage();

        return [
            'id' => $this->id,
            'store_id' => $this->store_id,
            'shopify_product_id' => $this->shopify_product_id,
            'title' => $this->title,
            'handle' => $this->handle,
            'slug' => $this->slug,
            'vendor' => $this->vendor?->name,
            'product_type' => $this->productType?->name,
            'category' => $this->category?->name,
            'category_id' => $this->category_id,
            'status' => $this->status,
            'is_active' => $this->status === 'active',
            'price_min' => $this->price_min,
            'price_max' => $this->price_max,
            'tags' => $this->tags,
            'featured_image' => $featuredImage,
            'variants_count' => $this->variants_count ?? null,
            'collections_count' => $this->collections_count ?? null,
            'files_count' => $this->files_count ?? null,
            'published_at' => $this->published_at,
            'shopify_updated_at' => $this->shopify_updated_at,
            'created_at' => $this->created_at,
        ];
    }

    private function featuredImage(): array
    {
        $featuredImage = is_array($this->featured_image) ? $this->featured_image : [];
        $file = $this->relationLoaded('files') ? $this->files->firstWhere('role', 'product_image') : null;
        $url = $featuredImage['url'] ?? $featuredImage['src'] ?? $file?->url ?? $this->image_url ?? null;

        return [
            'url' => $url,
            'alt' => $featuredImage['altText'] ?? $featuredImage['alt'] ?? $file?->altText ?? $this->title,
            'width' => $featuredImage['width'] ?? $file?->width ?? null,
            'height' => $featuredImage['height'] ?? $file?->height ?? null,
        ];
    }
}
