<?php

namespace App\Modules\Orders\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class OrderItemTableResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'store_id' => $this->store_id,
            'order_id' => $this->order_id,
            'order_number' => $this->order?->order_number,
            'variant_id' => $this->variant_id,
            'variant_title_source' => $this->variant?->title,
            'shopify_line_item_id' => $this->shopify_line_item_id,
            'shopify_product_id' => $this->shopify_product_id,
            'shopify_variant_id' => $this->shopify_variant_id,
            'product_title' => $this->product_title,
            'variant_title' => $this->variant_title,
            'sku' => $this->sku,
            'quantity' => $this->quantity,
            'unit_price' => $this->unit_price,
            'total_price' => $this->total_price,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
