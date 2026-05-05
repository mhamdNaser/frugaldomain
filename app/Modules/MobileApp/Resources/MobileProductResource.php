<?php

namespace App\Modules\MobileApp\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class MobileProductResource extends JsonResource
{
    public function toArray($request): array
    {
        $firstVariant = $this->relationLoaded('variants') ? $this->variants->first() : null;

        return [
            'id' => $this->id,
            'title' => $this->title,
            'handle' => $this->handle,
            'status' => $this->status,
            'description' => $this->description,
            'image' => $this->featured_image['url'] ?? $this->image_url,
            'price_min' => $this->price_min,
            'price_max' => $this->price_max,
            'currency' => $this->currency ?? null,
            'default_variant' => $firstVariant ? [
                'id' => $firstVariant->id,
                'title' => $firstVariant->title,
                'price' => $firstVariant->price,
                'compare_at_price' => $firstVariant->compare_at_price,
                'sku' => $firstVariant->sku,
                'inventory_quantity' => $firstVariant->inventory_quantity,
            ] : null,
            'variants' => $this->whenLoaded('variants', fn () => $this->variants->map(fn ($variant) => [
                'id' => $variant->id,
                'title' => $variant->title,
                'price' => $variant->price,
                'compare_at_price' => $variant->compare_at_price,
                'sku' => $variant->sku,
                'barcode' => $variant->barcode,
                'inventory_quantity' => $variant->inventory_quantity,
            ])->values()),
            'collections' => $this->whenLoaded('collections', fn () => $this->collections->map(fn ($collection) => [
                'id' => $collection->id,
                'title' => $collection->title,
                'handle' => $collection->handle,
            ])->values()),
            'tags' => $this->resolveTags(),
        ];
    }

    private function resolveTags(): array
    {
        if (!$this->relationLoaded('tags')) {
            return [];
        }

        $tags = $this->tags;

        if ($tags instanceof \Illuminate\Support\Collection) {
            return $tags
                ->map(fn ($tag) => is_object($tag) ? ($tag->name ?? null) : (is_array($tag) ? ($tag['name'] ?? null) : $tag))
                ->filter(fn ($name) => is_string($name) && trim($name) !== '')
                ->values()
                ->all();
        }

        if (is_array($tags)) {
            return collect($tags)
                ->map(fn ($tag) => is_object($tag) ? ($tag->name ?? null) : (is_array($tag) ? ($tag['name'] ?? null) : $tag))
                ->filter(fn ($name) => is_string($name) && trim($name) !== '')
                ->values()
                ->all();
        }

        return [];
    }
}
