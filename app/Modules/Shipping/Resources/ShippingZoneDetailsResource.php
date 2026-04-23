<?php

namespace App\Modules\Shipping\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ShippingZoneDetailsResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'store_id' => $this->store_id,
            'shopify_zone_id' => $this->shopify_zone_id,
            'shopify_profile_id' => $this->shopify_profile_id,
            'name' => $this->name,
            'countries' => $this->countries,
            'methods_count' => $this->methods_count ?? 0,
            'rates_count' => $this->rates_count ?? 0,
            'methods' => $this->methods->map(fn ($method) => [
                'id' => $method->id,
                'shipping_zone_id' => $method->shipping_zone_id,
                'shopify_zone_id' => $method->shopify_zone_id,
                'shopify_method_id' => $method->shopify_method_id,
                'name' => $method->name,
                'description' => $method->description,
                'is_active' => $method->is_active,
                'method_type' => $method->method_type,
                'conditions' => $method->conditions,
                'created_at' => $method->created_at,
                'updated_at' => $method->updated_at,
                'rates' => $method->rates->map(fn ($rate) => [
                    'id' => $rate->id,
                    'shipping_zone_id' => $rate->shipping_zone_id,
                    'shipping_method_id' => $rate->shipping_method_id,
                    'shopify_zone_id' => $rate->shopify_zone_id,
                    'shopify_method_id' => $rate->shopify_method_id,
                    'shopify_rate_id' => $rate->shopify_rate_id,
                    'name' => $rate->name,
                    'amount' => $rate->amount,
                    'currency' => $rate->currency,
                    'created_at' => $rate->created_at,
                    'updated_at' => $rate->updated_at,
                ])->values(),
            ])->values(),
            'rates' => $this->rates->map(fn ($rate) => [
                'id' => $rate->id,
                'shipping_zone_id' => $rate->shipping_zone_id,
                'shipping_method_id' => $rate->shipping_method_id,
                'shopify_zone_id' => $rate->shopify_zone_id,
                'shopify_method_id' => $rate->shopify_method_id,
                'shopify_rate_id' => $rate->shopify_rate_id,
                'name' => $rate->name,
                'amount' => $rate->amount,
                'currency' => $rate->currency,
                'created_at' => $rate->created_at,
                'updated_at' => $rate->updated_at,
            ])->values(),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
