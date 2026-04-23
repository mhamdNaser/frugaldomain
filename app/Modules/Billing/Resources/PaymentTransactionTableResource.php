<?php

namespace App\Modules\Billing\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PaymentTransactionTableResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'store_id' => $this->store_id,
            'order_id' => $this->order_id,
            'order_number' => $this->order?->order_number,
            'refund_id' => $this->refund_id,
            'shopify_transaction_id' => $this->shopify_transaction_id,
            'parent_shopify_transaction_id' => $this->parent_shopify_transaction_id,
            'gateway' => $this->gateway,
            'transaction_reference' => $this->transaction_reference,
            'kind' => $this->kind,
            'amount' => $this->amount,
            'currency' => $this->currency,
            'status' => $this->status,
            'test' => (bool) $this->test,
            'manual_payment_gateway' => (bool) $this->manual_payment_gateway,
            'processed_at' => $this->processed_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
