<?php

namespace App\Modules\Core\Repositories\Eloquent;

use App\Modules\Core\Repositories\Interfaces\PartnerDashboardStatisticsRepositoryInterface;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class PartnerDashboardStatisticsRepository implements PartnerDashboardStatisticsRepositoryInterface
{
    public function totalsForStore(string $storeId): array
    {
        $summary = [
            'products_count' => (int) DB::table('products')->where('store_id', $storeId)->count(),
            'variants_count' => (int) DB::table('product_variants')->where('store_id', $storeId)->count(),
            'orders_count' => (int) DB::table('orders')->where('store_id', $storeId)->count(),
            'customers_count' => (int) DB::table('customers')->where('store_id', $storeId)->count(),
            'collections_count' => (int) DB::table('collections')->where('store_id', $storeId)->count(),
            'inventory_total' => (int) DB::table('inventories')->where('store_id', $storeId)->sum('available_quantity'),
            'last_sync_at' => DB::table('sync_runs')->where('store_id', $storeId)->max('finished_at'),
            'sync_pending_count' => (int) DB::table('shopify_outbound_syncs')->where('store_id', $storeId)->whereIn('status', ['pending', 'retrying', 'processing'])->count(),
            'sync_failed_count' => (int) DB::table('shopify_outbound_syncs')->where('store_id', $storeId)->whereIn('status', ['failed', 'dead'])->count(),
            'sync_synced_count' => (int) DB::table('shopify_outbound_syncs')->where('store_id', $storeId)->where('status', 'synced')->count(),
            'active_products_count' => (int) DB::table('products')->where('store_id', $storeId)->where('status', 'active')->count(),
        ];

        $syncHealth = $this->syncHealth($storeId);
        $trends = $this->trends($storeId);
        $warehouseProducts = $this->warehouseProducts($storeId);

        return [
            'summary' => $summary,
            'sync_health' => $syncHealth,
            'trends' => $trends,
            'warehouse_products' => $warehouseProducts,
        ];
    }

    private function syncHealth(string $storeId): array
    {
        $default = [
            'pending' => 0,
            'processing' => 0,
            'retrying' => 0,
            'synced' => 0,
            'failed' => 0,
            'dead' => 0,
        ];

        $rows = DB::table('shopify_outbound_syncs')
            ->select('status', DB::raw('COUNT(*) as aggregate'))
            ->where('store_id', $storeId)
            ->groupBy('status')
            ->get();

        foreach ($rows as $row) {
            $status = strtolower((string) $row->status);
            if (array_key_exists($status, $default)) {
                $default[$status] = (int) $row->aggregate;
            }
        }

        $hasOutboundData = array_sum($default) > 0;
        if (!$hasOutboundData) {
            $runRows = DB::table('sync_runs')
                ->select('status', DB::raw('COUNT(*) as aggregate'))
                ->where('store_id', $storeId)
                ->groupBy('status')
                ->get();

            foreach ($runRows as $row) {
                $status = strtolower((string) $row->status);
                if (in_array($status, ['pending', 'running'], true)) {
                    $default['processing'] += (int) $row->aggregate;
                } elseif ($status === 'completed') {
                    $default['synced'] += (int) $row->aggregate;
                } elseif ($status === 'failed') {
                    $default['failed'] += (int) $row->aggregate;
                }
            }
        }

        return $default;
    }

    private function trends(string $storeId): array
    {
        $days = 14;
        $from = Carbon::now()->subDays($days - 1)->startOfDay();

        $syncRows = DB::table('sync_runs')
            ->selectRaw('DATE(created_at) as d, COUNT(*) as total, SUM(CASE WHEN status = "failed" THEN 1 ELSE 0 END) as failed, SUM(CASE WHEN status = "completed" THEN 1 ELSE 0 END) as completed')
            ->where('store_id', $storeId)
            ->where('created_at', '>=', $from)
            ->groupByRaw('DATE(created_at)')
            ->orderBy('d')
            ->get()
            ->keyBy('d');

        $orderRows = DB::table('orders')
            ->selectRaw('DATE(created_at) as d, COUNT(*) as total')
            ->where('store_id', $storeId)
            ->where('created_at', '>=', $from)
            ->groupByRaw('DATE(created_at)')
            ->orderBy('d')
            ->get()
            ->keyBy('d');

        $points = [];
        for ($i = 0; $i < $days; $i++) {
            $day = $from->copy()->addDays($i)->toDateString();
            $sync = $syncRows->get($day);
            $orders = $orderRows->get($day);

            $points[] = [
                'date' => $day,
                'sync_total' => (int) ($sync->total ?? 0),
                'sync_failed' => (int) ($sync->failed ?? 0),
                'sync_completed' => (int) ($sync->completed ?? 0),
                'orders' => (int) ($orders->total ?? 0),
            ];
        }

        return [
            'daily' => $points,
        ];
    }

    private function warehouseProducts(string $storeId): array
    {
        $rows = DB::table('products')
            ->select('warehouse_location', DB::raw('COUNT(*) as total'))
            ->where('store_id', $storeId)
            ->whereNull('deleted_at')
            ->groupBy('warehouse_location')
            ->orderByDesc('total')
            ->get();

        return $rows->map(fn ($row) => [
            'warehouse' => $row->warehouse_location ?: 'Not selected',
            'products_count' => (int) $row->total,
        ])->values()->all();
    }
}
