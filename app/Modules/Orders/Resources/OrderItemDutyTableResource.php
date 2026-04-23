<?php

namespace App\Modules\Orders\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class OrderItemDutyTableResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'store_id' => $this->store_id,
            'order_item_id' => $this->order_item_id,
            'order_duty_id' => $this->order_duty_id,
            'order_id' => $this->orderDuty?->order_id ?? $this->orderItem?->order_id,
            'product_title' => $this->orderItem?->product_title,
            'sku' => $this->orderItem?->sku,
            'shopify_line_item_id' => $this->shopify_line_item_id,
            'shopify_duty_id' => $this->shopify_duty_id,
            'harmonized_system_code' => $this->harmonized_system_code,
            'amount' => $this->amount,
            'currency' => $this->currency,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
