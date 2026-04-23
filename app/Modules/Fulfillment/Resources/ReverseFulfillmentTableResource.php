<?php

namespace App\Modules\Fulfillment\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ReverseFulfillmentTableResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'store_id' => $this->store_id,
            'order_return_id' => $this->order_return_id,
            'order_id' => $this->orderReturn?->order_id,
            'shopify_return_id' => $this->orderReturn?->shopify_return_id,
            'shopify_reverse_fulfillment_order_id' => $this->shopify_reverse_fulfillment_order_id,
            'status' => $this->status,
            'shopify_created_at' => $this->shopify_created_at,
            'shopify_updated_at' => $this->shopify_updated_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
