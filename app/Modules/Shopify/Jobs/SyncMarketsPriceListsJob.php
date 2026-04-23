<?php

namespace App\Modules\Shopify\Jobs;

use App\Modules\Shopify\Services\Sync\MarketsPriceListsSyncService;
use App\Modules\Shopify\Services\Sync\SyncRunTracker;
use App\Modules\Stores\Models\Store;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class SyncMarketsPriceListsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 600;

    public function __construct(
        public readonly string $storeId,
        public readonly ?int $syncRunId = null,
    ) {
        $this->onQueue('shopify-sync');
    }

    public function handle(MarketsPriceListsSyncService $service, SyncRunTracker $tracker): void
    {
        $syncJobId = $this->syncRunId
            ? $tracker->startJob($this->syncRunId, $this->storeId, 'markets-price-lists')
            : null;

        try {
            $count = $service->sync(Store::query()->findOrFail($this->storeId));

            if ($syncJobId) {
                $tracker->markJobCompleted($syncJobId);
            }
            if ($this->syncRunId) {
                $tracker->incrementRunCounts($this->syncRunId, fetched: $count, synced: $count);
                $tracker->markRunCompleted($this->syncRunId, $this->storeId, 'markets-price-lists');
            }
        } catch (Throwable $e) {
            if ($syncJobId) {
                $tracker->markJobFailed($syncJobId, $e);
            }
            if ($this->syncRunId) {
                $tracker->markRunFailed($this->syncRunId, $e, $this->storeId, 'markets-price-lists');
                $tracker->logError($this->syncRunId, $syncJobId, $this->storeId, 'markets-price-lists', $e);
            }

            throw $e;
        }
    }
}

