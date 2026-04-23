<?php

namespace App\Modules\Fulfillment\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class FulfillmentTableResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'store_id' => $this->store_id,
            'order_id' => $this->order_id,
            'order_number' => $this->order?->order_number,
            'fulfillment_service_id' => $this->fulfillment_service_id,
            'service_name' => $this->service?->service_name ?? $this->service?->name,
            'shopify_fulfillment_id' => $this->shopify_fulfillment_id,
            'shopify_order_id' => $this->shopify_order_id,
            'name' => $this->name,
            'status' => $this->status,
            'shipment_status' => $this->shipment_status,
            'tracking_company' => $this->tracking_company,
            'tracking_number' => $this->tracking_number,
            'tracking_url' => $this->tracking_url,
            'shipped_at' => $this->shopify_created_at,
            'delivered_at' => $this->shopify_updated_at,
            'items_count' => $this->items_count ?? 0,
            'tracking_count' => $this->tracking_count ?? 0,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
