<?php

namespace App\Modules\Shopify\Jobs;

use App\Modules\Shopify\Services\Sync\ContentSyncService;
use App\Modules\Shopify\Services\Sync\SyncRunTracker;
use App\Modules\Stores\Models\Store;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class SyncContentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 900;

    public function __construct(
        public readonly string $storeId,
        public readonly ?int $syncRunId = null,
    ) {
        $this->onQueue('shopify-content');
    }

    public function handle(ContentSyncService $service, SyncRunTracker $tracker): void
    {
        $syncJobId = $this->syncRunId
            ? $tracker->startJob($this->syncRunId, $this->storeId, 'content')
            : null;

        try {
            $result = $service->sync(Store::findOrFail($this->storeId));

            if ($syncJobId) {
                $tracker->markJobCompleted($syncJobId);
            }

            if ($this->syncRunId) {
                $fetched = array_sum(array_map(
                    static fn ($value): int => is_numeric($value) ? (int) $value : 0,
                    $result
                ));
                $tracker->incrementRunCounts($this->syncRunId, fetched: $fetched, synced: $fetched);
                $tracker->markRunCompleted($this->syncRunId, $this->storeId, 'content');
            }
        } catch (Throwable $e) {
            if ($syncJobId) {
                $tracker->markJobFailed($syncJobId, $e);
            }

            if ($this->syncRunId) {
                $tracker->markRunFailed($this->syncRunId, $e, $this->storeId, 'content');
                $tracker->logError($this->syncRunId, $syncJobId, $this->storeId, 'content', $e);
            }

            throw $e;
        }
    }

    public function failed(Throwable $exception): void
    {
        if (!$this->syncRunId) {
            return;
        }

        $tracker = app(SyncRunTracker::class);
        $tracker->markRunFailed($this->syncRunId, $exception, $this->storeId, 'content');
        $tracker->logError($this->syncRunId, null, $this->storeId, 'content', $exception);
    }
}
