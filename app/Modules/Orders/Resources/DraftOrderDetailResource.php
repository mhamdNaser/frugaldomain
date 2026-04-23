<?php

namespace App\Modules\Orders\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class DraftOrderDetailResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'store_id' => $this->store_id,
            'customer_id' => $this->customer_id,
            'shopify_customer_id' => $this->shopify_customer_id,
            'customer' => $this->whenLoaded('customer', fn () => [
                'id' => $this->customer?->id,
                'name' => $this->customer?->display_name,
                'email' => $this->customer?->email,
                'phone' => $this->customer?->phone,
            ]),
            'shopify_draft_order_id' => $this->shopify_draft_order_id,
            'name' => $this->name,
            'status' => $this->status,
            'invoice_url' => $this->invoice_url,
            'subtotal' => $this->subtotal,
            'tax' => $this->tax,
            'shipping' => $this->shipping ?? null,
            'discount' => $this->discount ?? null,
            'total' => $this->total,
            'currency' => $this->currency,
            'items_count' => $this->items_count ?? $this->items?->count() ?? 0,
            'items' => OrderLineItemResource::collection($this->whenLoaded('items')),
            'completed_at' => $this->completed_at,
            'shopify_created_at' => $this->shopify_created_at,
            'shopify_updated_at' => $this->shopify_updated_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
