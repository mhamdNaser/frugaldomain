<?php

namespace App\Modules\Fulfillment\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class FulfillmentTrackingTableResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'store_id' => $this->store_id,
            'fulfillment_id' => $this->fulfillment_id,
            'order_id' => $this->fulfillment?->order_id,
            'company' => $this->company,
            'number' => $this->number,
            'url' => $this->url,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
