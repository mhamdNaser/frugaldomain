<?php

namespace App\Modules\Catalog\Repositories\Interfaces;

interface ProductsDashboardRepositoryInterface
{
    public function getProductsCount(string $storeId): int;

    public function getVariantsCount(string $storeId): int;

    public function getProductsCountByStatus(string $storeId, string $status): int;

    public function getTotalInventoryQuantity(string $storeId): int;

    public function getLastSyncRun(string $storeId): ?object;

    public function getSyncCountByStatus(string $storeId, string $status): int;
}
