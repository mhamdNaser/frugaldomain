<?php

namespace App\Modules\Shopify\Jobs;

use App\Modules\Shopify\Actions\SyncProductsAction;
use App\Modules\Shopify\Exceptions\ShopifySyncException;
use App\Modules\Stores\Models\Store;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class SyncStoreProductsJob implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 300;

    public function __construct(
        public readonly string $storeId,
        public readonly int $syncRunId,
        public readonly int $first = 20,
        public readonly ?string $after = null,
    ) {
        $this->onQueue('shopify-sync');
    }

    /**
     * @throws ShopifySyncException
     */
    public function handle(SyncProductsAction $syncProductsAction): void
    {
        $store = Store::query()->find($this->storeId);

        if (!$store) {
            $this->failJob('Store not found');
        }

        // نحدد الـ job record
        $jobId = DB::table('sync_jobs')->insertGetId([
            'sync_run_id' => $this->syncRunId,
            'store_id'    => $this->storeId,
            'type'        => 'products',
            'status'      => 'running',
            'payload'     => json_encode([
                'after' => $this->after,
                'first' => $this->first,
            ]),
            'started_at'  => now(),
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);

        try {
            $result = $syncProductsAction->execute(
                store: $store,
                first: $this->first,
                after: $this->after,
            );

            // تحديث job نفسه
            DB::table('sync_jobs')
                ->where('id', $jobId)
                ->update([
                    'status'        => 'completed',
                    'finished_at'   => now(),
                    'updated_at'    => now(),
                ]);

            // تحديث run (إجمالي فقط وليس تشغيل/حالة تشغيل)
            DB::table('sync_runs')
                ->where('id', $this->syncRunId)
                ->increment('fetched_count', (int) ($result['fetched_count'] ?? 0));

            DB::table('sync_runs')
                ->where('id', $this->syncRunId)
                ->increment('synced_count', (int) ($result['synced_count'] ?? 0));

        } catch (\Throwable $e) {

            DB::table('sync_jobs')
                ->where('id', $jobId)
                ->update([
                    'status'       => 'failed',
                    'finished_at'  => now(),
                    'error_message'=> $e->getMessage(),
                    'updated_at'   => now(),
                ]);

            $this->logSyncError($e);

            $this->failJob($e->getMessage());
        }

    }

    public function failed(\Throwable $exception): void
    {
        $this->logSyncError($exception);

        DB::table('sync_runs')
            ->where('id', $this->syncRunId)
            ->update([
                'status' => 'failed',
                'finished_at' => now(),
                'error_message' => $exception->getMessage(),
                'updated_at' => now(),
            ]);
    }

    private function failJob(string $message): void
    {
        throw new ShopifySyncException(
            message: $message,
            context: [
                'store_id' => $this->storeId,
                'sync_run_id' => $this->syncRunId,
                'after' => $this->after,
            ]
        );
    }

    private function logSyncError(\Throwable $e): void
    {
        DB::table('sync_errors')->insert([
            'sync_run_id' => $this->syncRunId,
            'store_id'    => $this->storeId,
            'type'        => 'products_sync',
            'message'     => $e->getMessage(),
            'context'     => json_encode([
                'after' => $this->after,
                'first' => $this->first,
            ]),
            'file'        => $e->getFile(),
            'line'        => $e->getLine(),
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);
    }
}
