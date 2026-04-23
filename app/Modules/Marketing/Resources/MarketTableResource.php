<?php

namespace App\Modules\Marketing\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class MarketTableResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'store_id' => $this->store_id,
            'shopify_market_id' => $this->shopify_market_id,
            'name' => $this->name,
            'handle' => $this->handle,
            'currency' => $this->currency,
            'enabled' => $this->enabled,
            'is_primary' => $this->is_primary,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}

