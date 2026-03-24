<?php

namespace App\Modules\Core\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Core\Services\DashboardStatisticsService;
use App\Modules\Core\Resources\DashboardStatisticsResource;

class DashboardController extends Controller
{
    protected $statisticsService;

    public function __construct(DashboardStatisticsService $statisticsService)
    {
        $this->statisticsService = $statisticsService;
    }

    public function statistics()
    {
        $statistics = $this->statisticsService->getStatistics();
        return new DashboardStatisticsResource($statistics);
    }

    /**
     * Get simple statistics for quick view
     */
    public function quickStats()
    {
        $statistics = $this->statisticsService->getStatistics();
        return response()->json([
            'status' => 'success',
            'data' => [
                'total_users' => $statistics['summary']['total_users'],
                'users_with_stores' => $statistics['summary']['users_with_stores'],
                'total_stores' => $statistics['summary']['total_stores'],
                'active_users' => $statistics['summary']['active_users'],
                'users_with_stores_percentage' => $statistics['percentages']['users_with_stores'],
            ],
        ]);
    }
}
