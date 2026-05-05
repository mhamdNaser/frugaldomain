<?php

namespace App\Modules\Core\Repositories\Interfaces;

interface PartnerDashboardStatisticsRepositoryInterface
{
    public function totalsForStore(string $storeId): array;
}

