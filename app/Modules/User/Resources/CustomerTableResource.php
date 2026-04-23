<?php

namespace App\Modules\User\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CustomerTableResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'shopify_customer_id' => $this->shopify_customer_id,
            'display_name' => $this->display_name,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'email' => $this->email,
            'phone' => $this->phone,
            'status' => $this->status,
            'state' => $this->state,
            'orders_count' => (int) ($this->orders_count ?? 0),
            'total_spent' => number_format((float) ($this->orders_total_spent ?? 0), 2, '.', ''),
            'currency' => $this->currency,
            'shopify_created_at' => $this->shopify_created_at,
            'shopify_updated_at' => $this->shopify_updated_at,
            'created_at' => $this->created_at,
        ];
    }
}
