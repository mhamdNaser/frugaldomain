<?php

namespace App\Modules\Catalog\Services;

use App\Modules\Catalog\Repositories\Interfaces\ProductsDashboardRepositoryInterface;
use App\Modules\Stores\Models\Store;
use App\Modules\User\Models\User;

class ProductsDashboardService
{
    public function __construct(
        private readonly ProductsDashboardRepositoryInterface $repository,
    ) {
    }

    public function getStatisticsForUser(User $user): array
    {
        /** @var Store|null $store */
        $store = $user->store;

        if (!$store) {
            return [
                'summary' => [
                    'products_count' => 0,
                    'variants_count' => 0,
                    'active_products_count' => 0,
                    'draft_products_count' => 0,
                    'archived_products_count' => 0,
                    'total_inventory_quantity' => 0,
                ],
                'sync' => [
                    'last_sync_status' => null,
                    'last_sync_at' => null,
                    'successful_syncs_count' => 0,
                    'failed_syncs_count' => 0,
                    'pending_syncs_count' => 0,
                ],
            ];
        }

        $storeId = (string) $store->id;
        $lastSync = $this->repository->getLastSyncRun($storeId);

        return [
            'summary' => [
                'products_count' => $this->repository->getProductsCount($storeId),
                'variants_count' => $this->repository->getVariantsCount($storeId),
                'active_products_count' => $this->repository->getProductsCountByStatus($storeId, 'active'),
                'draft_products_count' => $this->repository->getProductsCountByStatus($storeId, 'draft'),
                'archived_products_count' => $this->repository->getProductsCountByStatus($storeId, 'archived'),
                'total_inventory_quantity' => $this->repository->getTotalInventoryQuantity($storeId),
            ],
            'sync' => [
                'last_sync_status' => $lastSync->status ?? null,
                'last_sync_at' => $lastSync->finished_at ?? $lastSync->created_at ?? null,
                'successful_syncs_count' => $this->repository->getSyncCountByStatus($storeId, 'completed'),
                'failed_syncs_count' => $this->repository->getSyncCountByStatus($storeId, 'failed'),
                'pending_syncs_count' => $this->repository->getSyncCountByStatus($storeId, 'pending'),
            ],
        ];
    }
}
