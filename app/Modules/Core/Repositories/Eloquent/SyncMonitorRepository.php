<?php

namespace App\Modules\Core\Repositories\Eloquent;

use App\Modules\Core\Repositories\Interfaces\SyncMonitorRepositoryInterface;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class SyncMonitorRepository implements SyncMonitorRepositoryInterface
{
    private const TYPES = ['runs', 'status', 'errors', 'meta'];

    public function allowedTypes(): array
    {
        return self::TYPES;
    }

    public function all(string $type, ?string $storeId = null, ?string $search = null, int $rowsPerPage = 10, int $page = 1)
    {
        $rowsPerPage = max(1, min($rowsPerPage, 100));
        $page = max(1, $page);

        return $this->queryFor($type, $storeId, $search)
            ->orderByDesc($this->orderColumn($type))
            ->paginate($rowsPerPage, ['*'], 'page', $page);
    }

    private function queryFor(string $type, ?string $storeId, ?string $search): Builder
    {
        return match ($type) {
            'runs' => $this->runsQuery($storeId, $search),
            'status' => $this->statusQuery($storeId, $search),
            'errors' => $this->errorsQuery($storeId, $search),
        };
    }

    private function runsQuery(?string $storeId, ?string $search): Builder
    {
        return DB::table('sync_runs')
            ->select('sync_runs.*')
            ->selectSub(fn ($query) => $query
                ->from('sync_jobs')
                ->selectRaw('count(*)')
                ->whereColumn('sync_jobs.sync_run_id', 'sync_runs.id'), 'jobs_count')
            ->selectSub(fn ($query) => $query
                ->from('sync_jobs')
                ->selectRaw('count(*)')
                ->whereColumn('sync_jobs.sync_run_id', 'sync_runs.id')
                ->where('sync_jobs.status', 'success'), 'successful_jobs_count')
            ->selectSub(fn ($query) => $query
                ->from('sync_jobs')
                ->selectRaw('count(*)')
                ->whereColumn('sync_jobs.sync_run_id', 'sync_runs.id')
                ->where('sync_jobs.status', 'failed'), 'failed_jobs_count')
            ->when($storeId, fn ($query) => $query->where('store_id', $storeId))
            ->when($search, fn ($query) => $query->where(function ($query) use ($search) {
                $query->where('type', 'like', "%{$search}%")
                    ->orWhere('trigger', 'like', "%{$search}%")
                    ->orWhere('status', 'like', "%{$search}%")
                    ->orWhere('batch_id', 'like', "%{$search}%")
                    ->orWhere('error_message', 'like', "%{$search}%")
                    ->orWhere('correlation_id', 'like', "%{$search}%");
            }));
    }

    private function statusQuery(?string $storeId, ?string $search): Builder
    {
        return DB::table('sync_metas')
            ->when($storeId, fn ($query) => $query->where('store_id', $storeId))
            ->when($search, fn ($query) => $query->where(function ($query) use ($search) {
                $query->where('entity_type', 'like', "%{$search}%")
                    ->orWhere('sync_status', 'like', "%{$search}%")
                    ->orWhere('cursor', 'like', "%{$search}%")
                    ->orWhere('error_message', 'like', "%{$search}%");
            }));
    }

    private function errorsQuery(?string $storeId, ?string $search): Builder
    {
        return DB::table('sync_errors')
            ->when($storeId, fn ($query) => $query->where('store_id', $storeId))
            ->when($search, fn ($query) => $query->where(function ($query) use ($search) {
                $query->where('type', 'like', "%{$search}%")
                    ->orWhere('message', 'like', "%{$search}%")
                    ->orWhere('file', 'like', "%{$search}%")
                    ->orWhere('context', 'like', "%{$search}%");
            }));
    }

    private function orderColumn(string $type): string
    {
        return match ($type) {
            'status' => 'updated_at',
            default => 'id',
        };
    }
}
