<?php

namespace App\Modules\Orders\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class OrderTableResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'customer' => $this->customer?->display_name,
            'shopify_order_id' => $this->shopify_order_id,
            'order_number' => $this->order_number,
            'status' => $this->status,
            'payment_status' => $this->payment_status,
            'fulfillment_status' => $this->fulfillment_status,
            'subtotal' => $this->subtotal,
            'tax' => $this->tax,
            'shipping' => $this->shipping,
            'discount' => $this->discount,
            'total' => $this->total,
            'currency' => $this->currency,
            'items_count' => $this->items_count ?? 0,
            'channel_name' => $this->channel?->channel_name,
            'source_name' => $this->channel?->source_name,
            'app_title' => $this->channel?->app_title,
            'risk_level' => $this->latestRisk?->risk_level,
            'risk_recommendation' => $this->latestRisk?->recommendation,
            'risk_provider' => $this->latestRisk?->provider,
            'placed_at' => $this->placed_at,
        ];
    }
}
