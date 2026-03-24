<?php

namespace App\Modules\Core\Services;

use App\Modules\Core\Repositories\Interfaces\DashboardRepositoryInterface;
use App\Modules\Stores\Models\Store;
use App\Modules\User\Models\User;

class DashboardStatisticsService
{
    protected $repository;

    public function __construct(DashboardRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    public function getStatistics(): array
    {
        $totalUsers = $this->repository->getTotalUsers();
        $usersWithStores = $this->repository->getUsersWithStores();
        $totalStores = $this->repository->getTotalStores();
        $activeUsers = $this->repository->getActiveUsers();

        return [
            'summary' => [
                'total_users' => $totalUsers,
                'users_with_stores' => $usersWithStores,
                'total_stores' => $totalStores,
                'active_users' => $activeUsers,
            ],
            'percentages' => [
                'users_with_stores' => $this->calculatePercentage($usersWithStores, $totalUsers),
                'active_users' => $this->calculatePercentage($activeUsers, $totalUsers),
                'stores_to_users_ratio' => $this->calculateStoresToUsersRatio($totalStores, $totalUsers),
            ],
            'charts' => [
                'users_growth' => $this->getUsersGrowthLastMonths(6),
                'stores_growth' => $this->getStoresGrowthLastMonths(6),
            ],
            'last_updated' => now()->toDateTimeString(),
        ];
    }

    private function calculatePercentage(int $part, int $total): float
    {
        if ($total === 0) {
            return 0.0;
        }

        return round(($part / $total) * 100, 2);
    }

    private function calculateStoresToUsersRatio(int $stores, int $users): float
    {
        if ($users === 0) {
            return 0.0;
        }

        return round($stores / $users, 2);
    }

    private function getUsersGrowthLastMonths(int $months = 6): array
    {
        $growth = [];

        for ($i = $months - 1; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $monthName = $date->format('M Y');

            $count = User::whereYear('created_at', $date->year)
                ->whereMonth('created_at', $date->month)
                ->count();

            $growth[] = [
                'month' => $monthName,
                'count' => $count,
            ];
        }

        return $growth;
    }

    private function getStoresGrowthLastMonths(int $months = 6): array
    {
        $growth = [];

        for ($i = $months - 1; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $monthName = $date->format('M Y');

            $count = Store::whereYear('created_at', $date->year)
                ->whereMonth('created_at', $date->month)
                ->count();

            $growth[] = [
                'month' => $monthName,
                'count' => $count,
            ];
        }

        return $growth;
    }
}
