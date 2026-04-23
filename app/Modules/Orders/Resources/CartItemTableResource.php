<?php

namespace App\Modules\Orders\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CartItemTableResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'store_id' => $this->store_id,
            'cart_id' => $this->cart_id,
            'cart_status' => $this->cart?->status,
            'variant_id' => $this->variant_id,
            'variant_title' => $this->variant?->title,
            'variant_sku' => $this->variant?->sku,
            'quantity' => $this->quantity,
            'unit_price' => $this->unit_price,
            'total_price' => $this->total_price,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
