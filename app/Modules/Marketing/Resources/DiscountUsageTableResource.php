<?php

namespace App\Modules\Marketing\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class DiscountUsageTableResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'store_id' => $this->store_id,
            'discount_id' => $this->discount_id,
            'discount_title' => $this->discount?->title,
            'order_id' => $this->order_id,
            'order_number' => $this->order?->order_number,
            'shopify_order_id' => $this->shopify_order_id,
            'code' => $this->code,
            'usage_count' => $this->usage_count,
            'total_sales' => $this->total_sales,
            'currency' => $this->currency,
            'used_at' => $this->created_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
