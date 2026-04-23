<?php

namespace App\Modules\Catalog\Repositories\Eloquent;

use Illuminate\Support\Facades\DB;
use App\Modules\Catalog\Repositories\Interfaces\ProductsDashboardRepositoryInterface;

class ProductsDashboardRepository implements ProductsDashboardRepositoryInterface
{
    public function getProductsCount(string $storeId): int
    {
        return DB::table('products')
            ->where('store_id', $storeId)
            ->count();
    }

    public function getVariantsCount(string $storeId): int
    {
        return DB::table('product_variants')
            ->where('store_id', $storeId)
            ->count();
    }

    public function getProductsCountByStatus(string $storeId, string $status): int
    {
        return DB::table('products')
            ->where('store_id', $storeId)
            ->whereRaw('LOWER(status) = ?', [strtolower($status)])
            ->count();
    }

    public function getTotalInventoryQuantity(string $storeId): int
    {
        return (int) DB::table('inventories')
            ->where('store_id', $storeId)
            ->sum('available_quantity');
    }

    public function getLastSyncRun(string $storeId): ?object
    {
        return DB::table('sync_runs')
            ->where('store_id', $storeId)
            ->where('type', 'products')
            ->latest('id')
            ->first();
    }

    public function getSyncCountByStatus(string $storeId, string $status): int
    {
        return DB::table('sync_runs')
            ->where('store_id', $storeId)
            ->where('type', 'products')
            ->where('status', $status)
            ->count();
    }
}
