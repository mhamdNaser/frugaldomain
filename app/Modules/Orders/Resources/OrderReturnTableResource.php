<?php

namespace App\Modules\Orders\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class OrderReturnTableResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'store_id' => $this->store_id,
            'order_id' => $this->order_id,
            'order_number' => $this->order?->order_number,
            'shopify_return_id' => $this->shopify_return_id,
            'status' => $this->status,
            'name' => $this->name,
            'items_count' => $this->items_count ?? 0,
            'requested_at' => $this->requested_at,
            'opened_at' => $this->opened_at,
            'closed_at' => $this->closed_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
