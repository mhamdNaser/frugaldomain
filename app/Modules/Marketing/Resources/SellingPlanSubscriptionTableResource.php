<?php

namespace App\Modules\Marketing\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class SellingPlanSubscriptionTableResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'store_id' => $this->store_id,
            'customer_id' => $this->customer_id,
            'shopify_subscription_contract_id' => $this->shopify_subscription_contract_id,
            'shopify_customer_id' => $this->shopify_customer_id,
            'status' => $this->status,
            'currency' => $this->currency,
            'next_billing_amount' => $this->next_billing_amount,
            'next_billing_date' => $this->next_billing_date,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}

