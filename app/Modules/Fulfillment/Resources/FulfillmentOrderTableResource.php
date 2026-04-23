<?php

namespace App\Modules\Fulfillment\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class FulfillmentOrderTableResource extends JsonResource
{
    public function toArray($request): array
    {
        $deliveryMethod = $this->delivery_method;
        if (is_array($deliveryMethod)) {
            $deliveryMethod = $deliveryMethod['method_type'] ?? $deliveryMethod['title'] ?? json_encode($deliveryMethod);
        }

        return [
            'id' => $this->id,
            'store_id' => $this->store_id,
            'order_id' => $this->order_id,
            'order_number' => $this->order?->order_number,
            'fulfillment_service_id' => $this->fulfillment_service_id,
            'service_name' => $this->service?->service_name ?? $this->service?->name,
            'shopify_fulfillment_order_id' => $this->shopify_fulfillment_order_id,
            'shopify_order_id' => $this->shopify_order_id,
            'shopify_assigned_location_id' => $this->shopify_assigned_location_id,
            'assigned_location_name' => $this->assigned_location_name,
            'status' => $this->status,
            'request_status' => $this->request_status,
            'delivery_method' => $deliveryMethod,
            'items_count' => $this->items_count ?? 0,
            'fulfill_at' => $this->fulfill_at,
            'fulfill_by' => $this->fulfill_by,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
