<?php

namespace App\Modules\Shopify\Jobs;

use App\Modules\Shopify\Services\Sync\GlobalFilesSyncService;
use App\Modules\Shopify\Services\Sync\SyncRunTracker;
use App\Modules\Stores\Models\Store;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class SyncGlobalFilesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 600;

    public function __construct(
        public readonly string $storeId,
        public readonly ?int $syncRunId = null,
    ) {
        $this->onQueue('shopify-files');
    }

    public function handle(GlobalFilesSyncService $service, SyncRunTracker $tracker): void
    {
        $syncJobId = $this->syncRunId
            ? $tracker->startJob($this->syncRunId, $this->storeId, 'files')
            : null;

        try {
            $service->sync(Store::findOrFail($this->storeId));

            if ($syncJobId) {
                $tracker->markJobCompleted($syncJobId);
            }

            if ($this->syncRunId) {
                $tracker->markRunCompleted($this->syncRunId, $this->storeId, 'files');
            }
        } catch (Throwable $e) {
            if ($syncJobId) {
                $tracker->markJobFailed($syncJobId, $e);
            }

            if ($this->syncRunId) {
                $tracker->markRunFailed($this->syncRunId, $e, $this->storeId, 'files');
                $tracker->logError($this->syncRunId, $syncJobId, $this->storeId, 'files', $e);
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
        $tracker->markRunFailed($this->syncRunId, $exception, $this->storeId, 'files');
        $tracker->logError($this->syncRunId, null, $this->storeId, 'files', $exception);
    }
}