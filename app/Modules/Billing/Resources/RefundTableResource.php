<?php

namespace App\Modules\Billing\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class RefundTableResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'store_id' => $this->store_id,
            'order_id' => $this->order_id,
            'order_number' => $this->order?->order_number,
            'shopify_refund_id' => $this->shopify_refund_id,
            'note' => $this->note,
            'total' => $this->total,
            'currency' => $this->currency,
            'items_count' => $this->items_count ?? 0,
            'transactions_count' => $this->transactions_count ?? 0,
            'processed_at' => $this->processed_at,
            'shopify_created_at' => $this->shopify_created_at,
            'shopify_updated_at' => $this->shopify_updated_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
