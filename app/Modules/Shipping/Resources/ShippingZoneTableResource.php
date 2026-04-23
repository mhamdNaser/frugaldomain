<?php

namespace App\Modules\Shipping\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ShippingZoneTableResource extends JsonResource
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
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
