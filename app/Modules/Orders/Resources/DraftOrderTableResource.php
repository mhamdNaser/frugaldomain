<?php

namespace App\Modules\Orders\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class DraftOrderTableResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'store_id' => $this->store_id,
            'customer_id' => $this->customer_id,
            'customer' => $this->customer?->display_name,
            'shopify_customer_id' => $this->shopify_customer_id,
            'shopify_draft_order_id' => $this->shopify_draft_order_id,
            'name' => $this->name,
            'status' => $this->status,
            'invoice_url' => $this->invoice_url,
            'subtotal' => $this->subtotal,
            'tax' => $this->tax,
            'total' => $this->total,
            'currency' => $this->currency,
            'items_count' => $this->items_count ?? 0,
            'completed_at' => $this->completed_at,
        ];
    }
}
