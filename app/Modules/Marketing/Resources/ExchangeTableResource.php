<?php

namespace App\Modules\Marketing\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ExchangeTableResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'store_id' => $this->store_id,
            'order_return_id' => $this->order_return_id,
            'shopify_exchange_line_item_id' => $this->shopify_exchange_line_item_id,
            'shopify_line_item_id' => $this->shopify_line_item_id,
            'title' => $this->title,
            'quantity' => $this->quantity,
            'status' => $this->status,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}

