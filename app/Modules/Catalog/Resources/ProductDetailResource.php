<?php

namespace App\Modules\Catalog\Resources;

class ProductDetailResource extends ProductTableResource
{
    public function toArray($request): array
    {
        return array_merge(parent::toArray($request), [
            'vendor_id' => $this->vendor_id,
            'product_type_id' => $this->product_type_id,
            'category_id' => $this->category_id,
            'warehouse_location' => $this->warehouse_location,
            'vendor_details' => $this->whenLoaded('vendor', fn() => $this->vendor ? [
                'id' => $this->vendor->id,
                'store_id' => $this->vendor->store_id,
                'shopify_vendor_id' => $this->vendor->shopify_vendor_id,
                'name' => $this->vendor->name,
                'slug' => $this->vendor->slug,
                'email' => $this->vendor->email ?? null,
                'contact_phone' => $this->vendor->contact_phone ?? null,
                'description' => $this->vendor->description,
                'is_active' => (bool) $this->vendor->is_active,
                'meta_title' => $this->vendor->meta_title ?? null,
                'meta_description' => $this->vendor->meta_description ?? null,
            ] : null),
            'product_type_details' => $this->whenLoaded('productType', fn() => $this->productType ? [
                'id' => $this->productType->id,
                'store_id' => $this->productType->store_id,
                'shopify_product_type_id' => $this->productType->shopify_product_type_id,
                'name' => $this->productType->name,
                'slug' => $this->productType->slug,
                'description' => $this->productType->description,
                'products_count' => $this->productType->products_count ?? null,
            ] : null),
            'category_details' => $this->whenLoaded('category', fn() => $this->category ? [
                'id' => $this->category->id,
                'store_id' => $this->category->store_id ?? null,
                'shopify_category_id' => $this->category->shopify_category_id ?? null,
                'name' => $this->category->name,
                'slug' => $this->category->slug,
            ] : null),
            'collections' => $this->whenLoaded('collections', fn() => $this->collections->map(fn($collection) => [
                'id' => $collection->id,
                'store_id' => $collection->store_id ?? null,
                'shopify_collection_id' => $collection->shopify_collection_id ?? null,
                'title' => $collection->title,
                'handle' => $collection->handle,
                'description' => $collection->description ?? null,
                'image_url' => $collection->image_url ?? null,
                'image_alt' => $collection->image_alt ?? null,
                'type' => $collection->type ?? null,
                'is_active' => isset($collection->is_active) ? (bool) $collection->is_active : null,
                'sort_order' => $collection->sort_order ?? null,
                'seo_title' => $collection->seo_title ?? null,
                'seo_description' => $collection->seo_description ?? null,
            ])->values()),
            'collection_ids' => $this->whenLoaded('collections', fn() => $this->collections->pluck('id')->values()),
            'description' => $this->description,
            'seo_title' => $this->seo_title,
            'seo_description' => $this->seo_description,
            'isGiftCard' => (bool) $this->isGiftCard,
            'hasOnlyDefaultVariant' => (bool) $this->hasOnlyDefaultVariant,
            'files' => ProductFileResource::collection($this->whenLoaded('files')),
            'file_ids' => $this->whenLoaded('files', fn() => $this->files->pluck('id')->values()),
            'media' => ProductMediaResource::collection($this->whenLoaded('productMedia')),
            'media_ids' => $this->whenLoaded('productMedia', fn() => $this->productMedia->pluck('id')->values()),
            'variants' => ProductVariantResource::collection($this->whenLoaded('variants')),
            'variant_ids' => $this->whenLoaded('variants', fn() => $this->variants->pluck('id')->values()),
            'options' => $this->whenLoaded('options', fn() => $this->options->map(fn($option) => [
                'id' => $option->id,
                'store_id' => $option->store_id ?? null,
                'name' => $option->name,
                'position' => $option->pivot?->position,
                'values' => $option->relationLoaded('values')
                    ? $option->values->map(fn($value) => [
                        'id' => $value->id,
                        'option_id' => $value->option_id ?? $option->id,
                        'label' => $value->label,
                        'value' => $value->value,
                        'is_color' => $this->isColorOption($option->name),
                        'color_hex' => $this->isColorOption($option->name) ? $this->colorHex($value->value) : null,
                    ])->values()
                    : [],
            ])->values()),
            'option_ids' => $this->whenLoaded('options', fn() => $this->options->pluck('id')->values()),
            'metafields' => $this->whenLoaded('metafields', fn() => $this->metafields->map(fn($metafield) => [
                'id' => $metafield->id,
                'store_id' => $metafield->store_id ?? null,
                'shopify_metafield_id' => $metafield->shopify_metafield_id ?? null,
                'namespace' => $metafield->namespace,
                'key' => $metafield->key ?? null,
                'type' => $metafield->type ?? null,
                'value' => $metafield->value ?? null,
                'metaobjects' => $metafield->relationLoaded('metaobjects')
                    ? $metafield->metaobjects->map(fn($metaobject) => [
                        'id' => $metaobject->id,
                        'shopify_metaobject_id' => $metaobject->shopify_metaobject_id ?? null,
                        'type' => $metaobject->type ?? null,
                        'handle' => $metaobject->handle ?? null,
                        'status' => $metaobject->status ?? null,
                        'fields' => $metaobject->fields ?? null,
                    ])->values()
                    : [],
            ])->values()),
            'metafield_ids' => $this->whenLoaded('metafields', fn() => $this->metafields->pluck('id')->values()),
            'metafield_namespaces' => $this->whenLoaded('metafields', fn() => $this->metafields
                ->pluck('namespace')
                ->filter()
                ->unique()
                ->values()),
            'raw_payload' => $this->raw_payload,
            'shopify_created_at' => $this->shopify_created_at,
            'shopify_updated_at' => $this->shopify_updated_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ]);
    }

    private function isColorOption(?string $name): bool
    {
        return in_array(strtolower((string) $name), ['color', 'colour', 'colors', 'colours'], true);
    }

    private function colorHex(?string $value): ?string
    {
        if (!$value) {
            return null;
        }

        $value = trim($value);

        if (preg_match('/^#(?:[0-9a-fA-F]{3}){1,2}$/', $value)) {
            return $value;
        }

        return null;
    }
}
