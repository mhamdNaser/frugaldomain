<?php

namespace App\Modules\Core\Services;

use App\Modules\Core\Repositories\Interfaces\PartnerDashboardStatisticsRepositoryInterface;
use App\Modules\User\Models\User;

class PartnerDashboardStatisticsService
{
    public function __construct(
        private readonly PartnerDashboardStatisticsRepositoryInterface $repository
    ) {}

    public function getForUser(User $user): array
    {
        $storeId = (string) ($user->store?->id ?? '');

        if ($storeId === '') {
            return [
                'summary' => $this->emptySummary(),
                'sync_health' => $this->emptySyncHealth(),
                'trends' => ['daily' => []],
                'warehouse_products' => [],
                'last_updated' => now()->toDateTimeString(),
            ];
        }

        $data = $this->repository->totalsForStore($storeId);

        return [
            'summary' => $data['summary'] ?? $this->emptySummary(),
            'sync_health' => $data['sync_health'] ?? $this->emptySyncHealth(),
            'trends' => $data['trends'] ?? ['daily' => []],
            'warehouse_products' => $data['warehouse_products'] ?? [],
            'last_updated' => now()->toDateTimeString(),
        ];
    }

    private function emptySummary(): array
    {
        return [
            'products_count' => 0,
            'variants_count' => 0,
            'orders_count' => 0,
            'customers_count' => 0,
            'collections_count' => 0,
            'inventory_total' => 0,
            'last_sync_at' => null,
            'sync_pending_count' => 0,
            'sync_failed_count' => 0,
            'sync_synced_count' => 0,
            'active_products_count' => 0,
        ];
    }

    private function emptySyncHealth(): array
    {
        return [
            'pending' => 0,
            'processing' => 0,
            'retrying' => 0,
            'synced' => 0,
            'failed' => 0,
            'dead' => 0,
        ];
    }
}
