<?php

namespace App\Modules\Billing\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PlanTableResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'price' => $this->price,
            'billing_interval' => $this->billing_interval,
            'trial_days' => $this->trial_days,
            'is_active' => (bool) $this->is_active,
            'subscriptions_count' => $this->subscriptions_count ?? 0,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'deleted_at' => $this->deleted_at,
        ];
    }
}
