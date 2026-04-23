<?php

namespace App\Modules\Stores\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class StoreSettingTableResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'store_id' => $this->store_id,
            'allow_guest_checkout' => $this->allow_guest_checkout,
            'enable_cod' => $this->enable_cod,
            'enable_stripe' => $this->enable_stripe,
            'tax_included' => $this->tax_included,
            'currency_format' => $this->currency_format,
            'weight_unit' => $this->weight_unit,
            'default_language' => $this->default_language,
            'push_notifications_enabled' => $this->push_notifications_enabled,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}

