<?php

namespace App\Modules\Catalog\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ProductVariantResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'store_id' => $this->store_id,
            'product_id' => $this->product_id,
            'shopify_variant_id' => $this->shopify_variant_id,
            'title' => $this->title,
            'sku' => $this->sku,
            'barcode' => $this->barcode,
            'price' => $this->price,
            'compare_at_price' => $this->compare_at_price,
            'inventory_quantity' => $this->inventory_quantity,
            'is_default' => (bool) $this->is_default,
            'available_for_sale' => (bool) $this->availableForSale,
            'taxable' => (bool) $this->taxable,
            'position' => $this->position,
            'image' => new ProductFileResource($this->whenLoaded('files', fn () => $this->files->first())),
            'files' => ProductFileResource::collection($this->whenLoaded('files')),
            'option_values' => $this->whenLoaded('optionValues', fn () => $this->optionValues->map(fn ($value) => [
                'id' => $value->id,
                'option_id' => $value->option_id,
                'option' => $value->option?->name,
                'label' => $value->label,
                'value' => $value->value,
                'is_color' => $this->isColorOption($value->option?->name),
                'color_hex' => $this->isColorOption($value->option?->name) ? $this->colorHex($value->value) : null,
            ])->values()),
            'metafields' => $this->whenLoaded('metafields', fn () => $this->metafields->map(fn ($metafield) => [
                'id' => $metafield->id,
                'store_id' => $metafield->store_id,
                'shopify_metafield_id' => $metafield->shopify_metafield_id,
                'namespace' => $metafield->namespace,
                'key' => $metafield->key,
                'type' => $metafield->type,
                'value' => $metafield->value,
                'metaobjects' => $metafield->relationLoaded('metaobjects')
                    ? $metafield->metaobjects->map(fn ($metaobject) => [
                        'id' => $metaobject->id,
                        'shopify_metaobject_id' => $metaobject->shopify_metaobject_id,
                        'type' => $metaobject->type,
                        'fields' => $metaobject->fields,
                    ])->values()
                    : [],
            ])->values()),
            'price_lists' => $this->whenLoaded('priceLists', fn () => $this->priceLists->map(fn ($priceList) => [
                'id' => $priceList->id,
                'store_id' => $priceList->store_id,
                'market_id' => $priceList->market_id,
                'shopify_price_list_id' => $priceList->shopify_price_list_id,
                'shopify_catalog_id' => $priceList->shopify_catalog_id,
                'name' => $priceList->name,
                'currency' => $priceList->currency,
                'fixed_prices_count' => $priceList->fixed_prices_count,
            ])->values()),
            'price_list_items' => $this->whenLoaded('priceListItems', fn () => $this->priceListItems->map(fn ($item) => [
                'id' => $item->id,
                'store_id' => $item->store_id,
                'price_list_id' => $item->price_list_id,
                'product_variant_id' => $item->product_variant_id,
                'shopify_variant_id' => $item->shopify_variant_id,
                'amount' => $item->amount,
                'compare_at_amount' => $item->compare_at_amount,
                'currency' => $item->currency,
                'origin_type' => $item->origin_type,
                'price_list' => $item->relationLoaded('priceList') ? [
                    'id' => $item->priceList?->id,
                    'name' => $item->priceList?->name,
                    'currency' => $item->priceList?->currency,
                    'shopify_price_list_id' => $item->priceList?->shopify_price_list_id,
                ] : null,
            ])->values()),
            'inventories' => $this->whenLoaded('inventories', fn () => $this->inventories->map(fn ($inventory) => [
                'id' => $inventory->id,
                'store_id' => $inventory->store_id,
                'product_variant_id' => $inventory->product_variant_id,
                'shopify_inventory_item_id' => $inventory->shopify_inventory_item_id,
                'location_id' => $inventory->location_id,
                'available_quantity' => $inventory->available_quantity,
                'tracked' => (bool) $inventory->tracked,
                'requires_shipping' => (bool) $inventory->requires_shipping,
                'weight' => $inventory->weight,
                'weight_unit' => $inventory->weight_unit,
                'location' => $inventory->relationLoaded('location') ? [
                    'id' => $inventory->location?->id,
                    'shopify_location_id' => $inventory->location?->shopify_location_id,
                    'name' => $inventory->location?->name,
                    'city' => $inventory->location?->city,
                    'country' => $inventory->location?->country,
                ] : null,
            ])->values()),
            'inventory_levels' => $this->whenLoaded('inventoryLevels', fn () => $this->inventoryLevels->map(fn ($level) => [
                'id' => $level->id,
                'store_id' => $level->store_id,
                'product_variant_id' => $level->product_variant_id,
                'inventory_item_id' => $level->inventory_item_id,
                'shopify_location_id' => $level->shopify_location_id,
                'available' => $level->available,
                'committed' => $level->committed,
                'incoming' => $level->incoming,
                'reserved' => $level->reserved,
                'on_hand' => $level->on_hand,
                'shopify_updated_at' => $level->shopify_updated_at,
                'location' => $level->relationLoaded('location') ? [
                    'id' => $level->location?->id,
                    'shopify_location_id' => $level->location?->shopify_location_id,
                    'name' => $level->location?->name,
                    'city' => $level->location?->city,
                    'country' => $level->location?->country,
                ] : null,
            ])->values()),
            'inventory_movements' => $this->whenLoaded('inventoryMovements', fn () => $this->inventoryMovements->map(fn ($movement) => [
                'id' => $movement->id,
                'store_id' => $movement->store_id,
                'product_variant_id' => $movement->product_variant_id,
                'location_id' => $movement->location_id,
                'type' => $movement->type,
                'quantity' => $movement->quantity,
                'before_quantity' => $movement->before_quantity,
                'after_quantity' => $movement->after_quantity,
                'reference_type' => $movement->reference_type,
                'reference_id' => $movement->reference_id,
                'user_id' => $movement->user_id,
                'created_at' => $movement->created_at,
                'updated_at' => $movement->updated_at,
                'location' => $movement->relationLoaded('location') ? [
                    'id' => $movement->location?->id,
                    'shopify_location_id' => $movement->location?->shopify_location_id,
                    'name' => $movement->location?->name,
                    'city' => $movement->location?->city,
                    'country' => $movement->location?->country,
                ] : null,
            ])->values()),
            'raw_payload' => $this->raw_payload,
            'shopify_created_at' => $this->shopify_created_at,
            'shopify_updated_at' => $this->shopify_updated_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
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
