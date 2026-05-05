<?php

namespace App\Modules\Stores\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class AccountsManageResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'store' => $this['store'] ?? null,
            'settings' => $this['settings'] ?? null,
            'branding' => $this['branding'] ?? null,
            'shopify' => $this['shopify'] ?? null,
        ];
    }
}

