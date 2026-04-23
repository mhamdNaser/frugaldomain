<?php

namespace App\Modules\Marketing\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class DiscountTableResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'store_id' => $this->store_id,
            'shopify_discount_id' => $this->shopify_discount_id,
            'discount_type' => $this->discount_type,
            'method' => $this->method,
            'title' => $this->title,
            'status' => $this->status,
            'summary' => $this->summary,
            'short_summary' => $this->short_summary,
            'usage_limit' => $this->usage_limit,
            'usage_count' => $this->usage_count,
            'total_sales' => $this->total_sales,
            'currency' => $this->currency,
            'codes_count' => $this->codes_count ?? 0,
            'usages_count' => $this->usages_count ?? 0,
            'starts_at' => $this->starts_at,
            'ends_at' => $this->ends_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
