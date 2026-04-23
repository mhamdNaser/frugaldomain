<?php

namespace App\Modules\Fulfillment\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class FulfillmentServiceTableResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'store_id' => $this->store_id,
            'shopify_fulfillment_service_id' => $this->shopify_fulfillment_service_id,
            'name' => $this->name,
            'email' => $this->email,
            'service_name' => $this->service_name,
            'type' => $this->type,
            'callback_url' => $this->callback_url,
            'fulfillments_count' => $this->fulfillments_count ?? 0,
            'fulfillment_orders_count' => $this->fulfillment_orders_count ?? 0,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
