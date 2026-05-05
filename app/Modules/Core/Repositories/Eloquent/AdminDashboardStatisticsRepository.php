<?php

namespace App\Modules\Core\Repositories\Eloquent;

use App\Modules\Core\Repositories\Interfaces\AdminDashboardStatisticsRepositoryInterface;
use Illuminate\Support\Facades\DB;

class AdminDashboardStatisticsRepository implements AdminDashboardStatisticsRepositoryInterface
{
    public function totals(): array
    {
        return [
            'users_count' => (int) DB::table('users')->count(),
            'stores_count' => (int) DB::table('stores')->count(),
            'products_count' => (int) DB::table('products')->count(),
            'orders_count' => (int) DB::table('orders')->count(),
            'customers_count' => (int) DB::table('customers')->count(),
            'sync_runs_count' => (int) DB::table('sync_runs')->count(),
            'active_stores_count' => (int) DB::table('stores')->whereRaw('LOWER(status) = ?', ['active'])->count(),
            'active_users_count' => (int) DB::table('users')->where(function ($q) {
                $q->whereRaw('LOWER(status) = ?', ['active'])->orWhere('is_active', true);
            })->count(),
        ];
    }
}

