<?php

namespace App\Modules\Inventory\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InventoryDetailResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $variantFile = $this->resolveVariantImage();

        return [
            'id' => $this->id,
            'store_id' => $this->store_id,
            'product_variant_id' => $this->product_variant_id,
            'inventory_item_id' => $this->inventory_item_id,
            'shopify_location_id' => $this->shopify_location_id,
            'available' => $this->available,
            'committed' => $this->committed,
            'incoming' => $this->incoming,
            'reserved' => $this->reserved,
            'on_hand' => $this->on_hand,
            'shopify_updated_at' => $this->shopify_updated_at,
            'raw_payload' => $this->raw_payload,
            'variant' => $this->whenLoaded('variant', fn () => [
                'id' => $this->variant?->id,
                'title' => $this->variant?->title,
                'sku' => $this->variant?->sku,
                'shopify_variant_id' => $this->variant?->shopify_variant_id,
                'image' => [
                    'url' => $variantFile?->url,
                    'alt' => $variantFile?->altText ?? $this->variant?->title,
                ],
                'product' => $this->variant?->product ? [
                    'id' => $this->variant->product->id,
                    'title' => $this->variant->product->title,
                    'handle' => $this->variant->product->handle,
                    'shopify_product_id' => $this->variant->product->shopify_product_id,
                ] : null,
            ]),
            'location' => $this->whenLoaded('location', fn () => [
                'id' => $this->location?->id,
                'name' => $this->location?->name,
                'address' => $this->location?->address,
                'city' => $this->location?->city,
                'country' => $this->location?->country,
                'shopify_location_id' => $this->location?->shopify_location_id,
                'is_active' => (bool) ($this->location?->is_active ?? false),
            ]),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }

    private function resolveVariantImage()
    {
        $variant = $this->variant;
        if (!$variant) {
            return null;
        }

        if ($variant->relationLoaded('variantImage') && $variant->variantImage) {
            return $variant->variantImage;
        }

        if ($variant->relationLoaded('files')) {
            $match = $variant->files->first(function ($file) use ($variant) {
                return (int) $file->fileable_id === (int) $variant->id
                    && $file->type === 'image'
                    && $file->role === 'variant_image';
            });

            if ($match) {
                return $match;
            }

            $image = $variant->files->first(function ($file) use ($variant) {
                return (int) $file->fileable_id === (int) $variant->id
                    && $file->type === 'image';
            });

            if ($image) {
                return $image;
            }
        }

        $payload = is_array($variant->raw_payload ?? null) ? $variant->raw_payload : [];
        $image = $payload['image'] ?? null;
        $url = is_array($image) ? ($image['src'] ?? $image['url'] ?? null) : (is_string($image) ? $image : null);

        if (!$url) {
            return null;
        }

        return (object) [
            'url' => $url,
            'altText' => $variant->title,
        ];
    }
}
