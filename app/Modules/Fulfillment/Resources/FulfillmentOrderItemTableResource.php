<?php

namespace App\Modules\Fulfillment\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class FulfillmentOrderItemTableResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'store_id' => $this->store_id,
            'fulfillment_order_id' => $this->fulfillment_order_id,
            'order_item_id' => $this->order_item_id,
            'order_id' => $this->orderItem?->order_id ?? $this->fulfillmentOrder?->order_id,
            'shopify_fulfillment_order_line_item_id' => $this->shopify_fulfillment_order_line_item_id,
            'shopify_line_item_id' => $this->shopify_line_item_id,
            'sku' => $this->orderItem?->sku,
            'title' => $this->orderItem?->product_title,
            'quantity' => $this->total_quantity,
            'fulfillable_quantity' => $this->remaining_quantity,
            'remaining_quantity' => $this->remaining_quantity,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
