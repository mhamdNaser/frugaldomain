<?php

namespace App\Modules\Core\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DashboardStatisticsResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'status' => 'success',
            'message' => 'Dashboard statistics retrieved successfully',
            'data' => [
                'summary' => [
                    'total_users' => $this['summary']['total_users'] ?? 0,
                    'users_with_stores' => $this['summary']['users_with_stores'] ?? 0,
                    'total_stores' => $this['summary']['total_stores'] ?? 0,
                    'active_users' => $this['summary']['active_users'] ?? 0,
                ],
                'percentages' => [
                    'users_with_stores' => ($this['percentages']['users_with_stores'] ?? 0) . '%',
                    'active_users' => ($this['percentages']['active_users'] ?? 0) . '%',
                    'stores_to_users_ratio' => $this['percentages']['stores_to_users_ratio'] ?? 0,
                ],
                'charts' => [
                    'users_growth' => $this['charts']['users_growth'] ?? [],
                    'stores_growth' => $this['charts']['stores_growth'] ?? [],
                ],
                'last_updated' => $this['last_updated'] ?? null,
            ],
            'metadata' => [
                'api_version' => '1.0',
                'server_time' => now()->toDateTimeString(),
            ],
        ];
    }
}
