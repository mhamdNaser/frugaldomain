<?php

namespace App\Modules\Shopify\Jobs;

use App\Modules\Shopify\Services\Sync\CustomersSyncService;
use App\Modules\Shopify\Services\Sync\SyncRunTracker;
use App\Modules\Stores\Models\Store;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class SyncCustomersJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 900;

    public function __construct(
        public readonly string $storeId,
        public readonly ?int $syncRunId = null,
    ) {
        $this->onQueue('shopify-customers');
    }

    public function handle(CustomersSyncService $service, SyncRunTracker $tracker): void
    {
        $syncJobId = $this->syncRunId
            ? $tracker->startJob($this->syncRunId, $this->storeId, 'customers')
            : null;

        try {
            $service->sync(Store::findOrFail($this->storeId));

            if ($syncJobId) {
                $tracker->markJobCompleted($syncJobId);
            }

            if ($this->syncRunId) {
                $tracker->markRunCompleted($this->syncRunId, $this->storeId, 'customers');
            }
        } catch (Throwable $e) {
            if ($syncJobId) {
                $tracker->markJobFailed($syncJobId, $e);
            }

            if ($this->syncRunId) {
                $tracker->markRunFailed($this->syncRunId, $e, $this->storeId, 'customers');
                $tracker->logError($this->syncRunId, $syncJobId, $this->storeId, 'customers', $e);
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
        $tracker->markRunFailed($this->syncRunId, $exception, $this->storeId, 'customers');
        $tracker->logError($this->syncRunId, null, $this->storeId, 'customers', $exception);
    }
}