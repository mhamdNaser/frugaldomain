<?php

namespace App\Modules\Core\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class AdminDashboardStatisticsResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'summary' => $this['summary'] ?? [],
            'last_updated' => $this['last_updated'] ?? null,
        ];
    }
}

