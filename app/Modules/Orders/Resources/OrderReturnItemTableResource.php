<?php

namespace App\Modules\Orders\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class OrderReturnItemTableResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'store_id' => $this->store_id,
            'order_return_id' => $this->order_return_id,
            'order_item_id' => $this->order_item_id,
            'order_id' => $this->orderReturn?->order_id ?? $this->orderItem?->order_id,
            'shopify_return_line_item_id' => $this->shopify_return_line_item_id,
            'shopify_line_item_id' => $this->shopify_line_item_id,
            'product_title' => $this->orderItem?->product_title,
            'sku' => $this->orderItem?->sku,
            'quantity' => $this->quantity,
            'reason' => $this->reason,
            'note' => $this->note,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
