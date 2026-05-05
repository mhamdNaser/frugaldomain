<?php

namespace App\Modules\Tax\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class TaxLineResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'store_id' => $this->store_id,
            'order_id' => $this->order_id,
            'order_item_id' => $this->order_item_id,
            'shopify_tax_line_id' => $this->shopify_tax_line_id,
            'source_key' => $this->source_key,
            'title' => $this->title,
            'rate' => $this->rate,
            'rate_percentage' => $this->rate_percentage,
            'price' => $this->price,
            'currency' => $this->currency,
            'channel_liable' => $this->channel_liable,
            'source' => $this->source,
            'is_shipping' => $this->is_shipping,
            'raw_payload' => $this->raw_payload,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
