<?php

namespace App\Modules\Orders\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class OrderDetailResource extends JsonResource
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
            'email' => $this->email,
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
            'items_count' => $this->items_count ?? $this->items?->count() ?? 0,
            'items' => OrderLineItemResource::collection($this->whenLoaded('items')),
            'channel' => $this->whenLoaded('channel', fn () => [
                'source_name' => $this->channel?->source_name,
                'source_identifier' => $this->channel?->source_identifier,
                'channel_id' => $this->channel?->channel_id,
                'channel_name' => $this->channel?->channel_name,
                'app_id' => $this->channel?->app_id,
                'app_title' => $this->channel?->app_title,
            ]),
            'risks' => $this->whenLoaded('risks', fn () => $this->risks->map(fn ($risk) => [
                'assessment_id' => $risk->assessment_id,
                'risk_level' => $risk->risk_level,
                'recommendation' => $risk->recommendation,
                'provider' => $risk->provider,
                'assessed_at' => $risk->assessed_at,
                'facts' => $risk->facts,
            ])->values()),
            'placed_at' => $this->placed_at,
            'shopify_created_at' => $this->shopify_created_at,
            'shopify_updated_at' => $this->shopify_updated_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
