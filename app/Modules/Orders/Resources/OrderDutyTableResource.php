<?php

namespace App\Modules\Orders\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class OrderDutyTableResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'store_id' => $this->store_id,
            'order_id' => $this->order_id,
            'order_number' => $this->order?->order_number,
            'shopify_order_id' => $this->shopify_order_id,
            'shopify_duty_id' => $this->shopify_duty_id,
            'harmonized_system_code' => $this->harmonized_system_code,
            'amount' => $this->amount,
            'currency' => $this->currency,
            'item_duties_count' => $this->item_duties_count ?? 0,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
