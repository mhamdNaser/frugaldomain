<?php

namespace App\Modules\Shopify\Jobs\Pagination;

use App\Modules\Shopify\Actions\SyncProductsAction;
use App\Modules\Shopify\Services\Sync\SyncRunTracker;
use App\Modules\Stores\Models\Store;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class SyncProductsPaginationOrchestratorJob implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 300;

    public function __construct(
        public readonly string $storeId,
        public readonly int $syncRunId,
        public readonly int $first = 100,
        public readonly ?string $after = null,
    ) {
        $this->onQueue('shopify-sync');
    }

    public function handle(SyncProductsAction $action, SyncRunTracker $tracker): void
    {
        $syncJobId = $tracker->startJob($this->syncRunId, $this->storeId, 'products', [
            'after' => $this->after,
            'first' => $this->first,
        ]);

        try {
            $store = Store::query()->findOrFail($this->storeId);
            $result = $action->execute(
                store: $store,
                first: $this->first,
                after: $this->after,
            );

            $tracker->incrementRunCounts(
                syncRunId: $this->syncRunId,
                fetched: (int) ($result['fetched_count'] ?? 0),
                synced: (int) ($result['synced_count'] ?? 0),
            );
            $tracker->markJobCompleted($syncJobId);

            $pageInfo = $result['page_info'] ?? [];

            if (!empty($pageInfo['has_next_page']) && !empty($pageInfo['end_cursor'])) {
                self::dispatch(
                    storeId: $this->storeId,
                    syncRunId: $this->syncRunId,
                    first: $this->first,
                    after: $pageInfo['end_cursor'],
                )->delay(now()->addSeconds(1));

                return;
            }

            $tracker->markRunCompleted($this->syncRunId, $this->storeId, 'products');
        } catch (Throwable $e) {
            $tracker->markJobFailed($syncJobId, $e);
            $tracker->markRunFailed($this->syncRunId, $e, $this->storeId, 'products');
            $tracker->logError($this->syncRunId, $syncJobId, $this->storeId, 'products', $e, [
                'after' => $this->after,
                'first' => $this->first,
            ]);

            throw $e;
        }
    }

    public function failed(Throwable $exception): void
    {
        $tracker = app(SyncRunTracker::class);
        $tracker->markRunFailed($this->syncRunId, $exception, $this->storeId, 'products');
        $tracker->logError($this->syncRunId, null, $this->storeId, 'products', $exception, [
            'after' => $this->after,
            'first' => $this->first,
        ]);
    }
}