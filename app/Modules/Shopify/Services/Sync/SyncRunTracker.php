<?php

namespace App\Modules\Shopify\Services\Sync;

use Illuminate\Support\Facades\DB;
use Throwable;

class SyncRunTracker
{
    public function startRun(string $storeId, string $type, string $trigger = 'manual', ?string $batchId = null): int
    {
        $syncRunId = DB::table('sync_runs')->insertGetId([
            'store_id' => $storeId,
            'type' => $type,
            'trigger' => $trigger,
            'status' => 'pending',
            'batch_id' => $batchId,
            'fetched_count' => 0,
            'synced_count' => 0,
            'failed_count' => 0,
            'error_message' => null,
            'correlation_id' => (string) str()->uuid(),
            'started_at' => null,
            'finished_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->updateMeta($storeId, $type, 'syncing');

        return $syncRunId;
    }

    public function attachBatch(int $syncRunId, string $batchId): void
    {
        DB::table('sync_runs')->where('id', $syncRunId)->update([
            'batch_id' => $batchId,
            'updated_at' => now(),
        ]);
    }

    public function markRunRunning(int $syncRunId): void
    {
        DB::table('sync_runs')->where('id', $syncRunId)->update([
            'status' => 'running',
            'started_at' => DB::raw('COALESCE(started_at, CURRENT_TIMESTAMP)'),
            'updated_at' => now(),
        ]);
    }

    public function markRunCompleted(int $syncRunId, ?string $storeId = null, ?string $type = null): void
    {
        DB::table('sync_runs')->where('id', $syncRunId)->update([
            'status' => 'completed',
            'finished_at' => now(),
            'error_message' => null,
            'updated_at' => now(),
        ]);

        if ($storeId && $type) {
            $this->updateMeta($storeId, $type, 'completed');
        }
    }

    public function markRunFailed(int $syncRunId, Throwable|string $error, ?string $storeId = null, ?string $type = null): void
    {
        $message = $error instanceof Throwable ? $error->getMessage() : $error;

        DB::table('sync_runs')->where('id', $syncRunId)->update([
            'status' => 'failed',
            'failed_count' => DB::raw('failed_count + 1'),
            'finished_at' => now(),
            'error_message' => $message,
            'updated_at' => now(),
        ]);

        if ($storeId && $type) {
            $this->updateMeta($storeId, $type, 'failed', $message);
        }
    }

    public function startJob(int $syncRunId, string $storeId, string $type, array $payload = []): int
    {
        $this->markRunRunning($syncRunId);

        return DB::table('sync_jobs')->insertGetId([
            'sync_run_id' => $syncRunId,
            'store_id' => $storeId,
            'type' => $type,
            'status' => 'running',
            'attempts' => 1,
            'payload' => $payload ? json_encode($payload) : null,
            'started_at' => now(),
            'finished_at' => null,
            'error_message' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function markJobCompleted(int $syncJobId): void
    {
        DB::table('sync_jobs')->where('id', $syncJobId)->update([
            'status' => 'success',
            'finished_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function markJobFailed(int $syncJobId, Throwable|string $error): void
    {
        $message = $error instanceof Throwable ? $error->getMessage() : $error;

        DB::table('sync_jobs')->where('id', $syncJobId)->update([
            'status' => 'failed',
            'finished_at' => now(),
            'error_message' => $message,
            'updated_at' => now(),
        ]);
    }

    public function incrementRunCounts(int $syncRunId, int $fetched = 0, int $synced = 0, int $failed = 0): void
    {
        DB::table('sync_runs')->where('id', $syncRunId)->update([
            'fetched_count' => DB::raw('fetched_count + ' . max(0, $fetched)),
            'synced_count' => DB::raw('synced_count + ' . max(0, $synced)),
            'failed_count' => DB::raw('failed_count + ' . max(0, $failed)),
            'updated_at' => now(),
        ]);
    }

    public function logError(?int $syncRunId, ?int $syncJobId, ?string $storeId, string $type, Throwable $error, array $context = []): void
    {
        DB::table('sync_errors')->insert([
            'sync_run_id' => $syncRunId,
            'sync_job_id' => $syncJobId,
            'store_id' => $storeId,
            'type' => $type,
            'message' => $error->getMessage(),
            'context' => $context ? json_encode($context) : null,
            'file' => $error->getFile(),
            'line' => $error->getLine(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function updateMeta(string $storeId, string $type, string $status, ?string $errorMessage = null, int $processedCount = 0, ?string $cursor = null): void
    {
        $existing = DB::table('sync_metas')
            ->where('store_id', $storeId)
            ->where('entity_type', $type)
            ->first();

        DB::table('sync_metas')->updateOrInsert(
            [
                'store_id' => $storeId,
                'entity_type' => $type,
            ],
            [
                'sync_status' => $status,
                'last_synced_at' => $status === 'completed' ? now() : ($existing->last_synced_at ?? null),
                'processed_count' => $processedCount ?: ($existing->processed_count ?? 0),
                'cursor' => $cursor,
                'error_message' => $errorMessage,
                'updated_at' => now(),
                'created_at' => $existing->created_at ?? now(),
            ]
        );
    }
}