<?php

namespace App\Modules\Stores\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class StoreResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'owner' => $this->owner->name,
            'owner_id' => $this->owner_id,
            'shopify_store_id' => $this->shopify_store_id,
            'shopify_domain' => $this->shopify_domain,
            'name' => $this->name,
            'email' => $this->email,
            'plan' => $this->plan,
            'currency' => $this->currency,
            'status' => $this->status,
        ];
    }
}
