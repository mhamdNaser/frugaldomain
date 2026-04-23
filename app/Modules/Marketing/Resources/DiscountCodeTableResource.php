<?php

namespace App\Modules\Marketing\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class DiscountCodeTableResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'store_id' => $this->store_id,
            'discount_id' => $this->discount_id,
            'discount_title' => $this->discount?->title,
            'shopify_discount_code_id' => $this->shopify_discount_code_id,
            'code' => $this->code,
            'usage_count' => $this->usage_count,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
