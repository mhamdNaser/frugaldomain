<?php

namespace App\Modules\Core\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Core\Services\DashboardStatisticsService;
use App\Modules\Core\Services\IconStatisticsService;
use App\Modules\Core\Resources\DashboardStatisticsResource;
use App\Modules\Core\Resources\IconStatisticsResource;

class DashboardController extends Controller
{
    protected $statisticsService;
    protected $iconStatisticsService;

    public function __construct(
        DashboardStatisticsService $statisticsService,
        IconStatisticsService $iconStatisticsService
    ) {
        $this->statisticsService = $statisticsService;
        $this->iconStatisticsService = $iconStatisticsService;
    }

    public function statistics()
    {
        $statistics = $this->statisticsService->getStatistics();
        return new DashboardStatisticsResource($statistics);
    }
    
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

    public function iconStatistics()
    {
        $statistics = $this->iconStatisticsService->getStatistics();
        return new IconStatisticsResource($statistics);
    }

    public function quickIconStats()
    {
        $statistics = $this->iconStatisticsService->getStatistics();

        return response()->json([
            'status' => 'success',
            'data' => [
                'total_icons' => $statistics['summary']['total_icons'],
                'total_files' => $statistics['summary']['total_files'],
                'total_downloads' => $statistics['summary']['total_downloads'],
                'svg_downloads' => $statistics['downloads']['svg_downloads'],
                'png_downloads' => $statistics['downloads']['png_downloads'],
                'icons_with_downloads_percentage' => $statistics['percentages']['icons_with_downloads_percentage'],
            ],
        ]);
    }
}
