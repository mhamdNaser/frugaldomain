<?php

namespace App\Modules\Shopify\Webhooks\Jobs;

use App\Modules\Shopify\Services\Sync\SyncRunTracker;
use App\Modules\Shopify\Webhooks\Services\ShopifyWebhookSyncJobMap;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class DispatchSyncTypeFromWebhookJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 120;

    public function __construct(
        public readonly string $storeId,
        public readonly string $syncType,
        public readonly ?string $webhookExternalId = null,
        public readonly ?string $topic = null,
    ) {
        $this->onQueue('shopify-sync');
    }

    public function handle(
        SyncRunTracker $tracker,
        ShopifyWebhookSyncJobMap $syncJobMap,
    ): void {
        $jobClass = $syncJobMap->jobClassFor($this->syncType);

        if (!$jobClass) {
            return;
        }

        $syncRunId = $tracker->startRun(
            storeId: $this->storeId,
            type: $this->syncType,
            trigger: 'webhook',
        );

        $jobClass::dispatch($this->storeId, $syncRunId);
    }
}

