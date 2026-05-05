<?php

namespace App\Modules\Core\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PartnerDashboardStatisticsResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'summary' => $this['summary'] ?? [],
            'sync_health' => $this['sync_health'] ?? [],
            'trends' => $this['trends'] ?? ['daily' => []],
            'warehouse_products' => $this['warehouse_products'] ?? [],
            'last_updated' => $this['last_updated'] ?? null,
        ];
    }
}
