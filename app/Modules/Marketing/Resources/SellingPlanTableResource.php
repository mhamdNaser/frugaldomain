<?php

namespace App\Modules\Marketing\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class SellingPlanTableResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'store_id' => $this->store_id,
            'selling_plan_group_id' => $this->selling_plan_group_id,
            'shopify_selling_plan_id' => $this->shopify_selling_plan_id,
            'name' => $this->name,
            'category' => $this->category,
            'billing_policy' => $this->billing_policy,
            'delivery_policy' => $this->delivery_policy,
            'pricing_policies' => $this->pricing_policies,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}

