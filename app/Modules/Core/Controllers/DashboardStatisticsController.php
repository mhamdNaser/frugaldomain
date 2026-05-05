<?php

namespace App\Modules\Core\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Core\Resources\AdminDashboardStatisticsResource;
use App\Modules\Core\Resources\PartnerDashboardStatisticsResource;
use App\Modules\Core\Services\AdminDashboardStatisticsService;
use App\Modules\Core\Services\PartnerDashboardStatisticsService;
use Illuminate\Http\Request;

class DashboardStatisticsController extends Controller
{
    public function __construct(
        private readonly AdminDashboardStatisticsService $adminService,
        private readonly PartnerDashboardStatisticsService $partnerService,
    ) {}

    public function admin(): AdminDashboardStatisticsResource
    {
        return new AdminDashboardStatisticsResource($this->adminService->get());
    }

    public function partner(Request $request): PartnerDashboardStatisticsResource
    {
        return new PartnerDashboardStatisticsResource(
            $this->partnerService->getForUser($request->user())
        );
    }
}

