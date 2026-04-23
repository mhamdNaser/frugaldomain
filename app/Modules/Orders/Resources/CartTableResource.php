<?php

namespace App\Modules\Orders\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CartTableResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'store_id' => $this->store_id,
            'customer_id' => $this->customer_id,
            'customer' => $this->customer?->display_name,
            'status' => $this->status,
            'total_amount' => $this->total_amount,
            'currency' => $this->currency,
            'items_count' => $this->items_count ?? 0,
            'expires_at' => $this->expires_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'deleted_at' => $this->deleted_at,
        ];
    }
}
