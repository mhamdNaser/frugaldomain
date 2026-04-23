<?php

namespace App\Modules\Orders\Resources;

use App\Modules\Catalog\Resources\ProductFileResource;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderLineItemResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'store_id' => $this->store_id,
            'variant_id' => $this->variant_id,
            'shopify_line_item_id' => $this->shopify_line_item_id,
            'shopify_product_id' => $this->shopify_product_id,
            'shopify_variant_id' => $this->shopify_variant_id,
            'product_title' => $this->product_title,
            'variant_title' => $this->variant_title,
            'sku' => $this->sku,
            'quantity' => $this->quantity,
            'unit_price' => $this->unit_price,
            'total_price' => $this->total_price,
            'variant' => $this->whenLoaded('variant', fn () => [
                'id' => $this->variant?->id,
                'title' => $this->variant?->title,
                'sku' => $this->variant?->sku,
                'barcode' => $this->variant?->barcode,
                'price' => $this->variant?->price,
                'compare_at_price' => $this->variant?->compare_at_price,
                'inventory_quantity' => $this->variant?->inventory_quantity,
                'image' => new ProductFileResource($this->variant?->files?->first()),
                'files' => ProductFileResource::collection($this->variant?->files ?? collect()),
                'option_values' => $this->variant?->relationLoaded('optionValues')
                    ? $this->variant->optionValues->map(fn ($value) => [
                        'id' => $value->id,
                        'option_id' => $value->option_id,
                        'option' => $value->option?->name,
                        'label' => $value->label,
                        'value' => $value->value,
                    ])->values()
                    : [],
                'product' => $this->variant?->relationLoaded('product') ? [
                    'id' => $this->variant?->product?->id,
                    'title' => $this->variant?->product?->title,
                    'handle' => $this->variant?->product?->handle,
                    'featured_image' => $this->variant?->product?->featured_image,
                ] : null,
            ]),
            'raw_payload' => $this->raw_payload,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
