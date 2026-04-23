<?php

namespace App\Modules\Billing\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class RefundItemTableResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'store_id' => $this->store_id,
            'refund_id' => $this->refund_id,
            'shopify_refund_id' => $this->refund?->shopify_refund_id,
            'order_item_id' => $this->order_item_id,
            'order_item_title' => $this->orderItem?->product_title,
            'shopify_refund_line_item_id' => $this->shopify_refund_line_item_id,
            'shopify_line_item_id' => $this->shopify_line_item_id,
            'quantity' => $this->quantity,
            'restock_type' => $this->restock_type,
            'restocked' => (bool) $this->restocked,
            'subtotal' => $this->subtotal,
            'tax' => $this->tax,
            'total' => $this->total,
            'currency' => $this->currency,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
