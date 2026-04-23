<?php

namespace App\Modules\Marketing\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class SellingPlanGroupTableResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'store_id' => $this->store_id,
            'shopify_selling_plan_group_id' => $this->shopify_selling_plan_group_id,
            'name' => $this->name,
            'app_id' => $this->app_id,
            'summary' => $this->summary,
            'options' => $this->options,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}

