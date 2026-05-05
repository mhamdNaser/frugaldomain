<?php

namespace App\Modules\Core\Services;

use App\Modules\Core\Repositories\Interfaces\AdminDashboardStatisticsRepositoryInterface;

class AdminDashboardStatisticsService
{
    public function __construct(
        private readonly AdminDashboardStatisticsRepositoryInterface $repository
    ) {}

    public function get(): array
    {
        return [
            'summary' => $this->repository->totals(),
            'last_updated' => now()->toDateTimeString(),
        ];
    }
}

