<?php

namespace App\Modules\Shopify\Jobs;

use App\Modules\Shopify\Services\Sync\SyncRunTracker;
use App\Modules\Shopify\Services\Sync\ThemesSyncService;
use App\Modules\Stores\Models\Store;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class SyncThemesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 600;

    public function __construct(
        public readonly string $storeId,
        public readonly ?int $syncRunId = null,
    ) {
        $this->onQueue('shopify-content');
    }

    public function handle(ThemesSyncService $service, SyncRunTracker $tracker): void
    {
        $syncJobId = $this->syncRunId
            ? $tracker->startJob($this->syncRunId, $this->storeId, 'themes')
            : null;

        try {
            $result = $service->sync(Store::query()->findOrFail($this->storeId));
            $fetched = (int) (($result['themes'] ?? 0) + ($result['theme_assets'] ?? 0));

            if ($syncJobId) {
                $tracker->markJobCompleted($syncJobId);
            }

            if ($this->syncRunId) {
                $tracker->incrementRunCounts($this->syncRunId, fetched: $fetched, synced: $fetched);
                $tracker->markRunCompleted($this->syncRunId, $this->storeId, 'themes');
            }
        } catch (Throwable $e) {
            if ($syncJobId) {
                $tracker->markJobFailed($syncJobId, $e);
            }

            if ($this->syncRunId) {
                $tracker->markRunFailed($this->syncRunId, $e, $this->storeId, 'themes');
                $tracker->logError($this->syncRunId, $syncJobId, $this->storeId, 'themes', $e);
            }

            throw $e;
        }
    }
}

