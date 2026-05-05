<?php

namespace App\Modules\Shopify\OutboundSync\Jobs;

use App\Modules\Shopify\OutboundSync\Services\OutboundSyncProcessor;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessOutboundSyncJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;
    public int $timeout = 120;

    public function __construct(
        public readonly int $outboundSyncId,
    ) {
        $this->onQueue('shopify-outbound');
    }

    public function handle(OutboundSyncProcessor $processor): void
    {
        $processor->process($this->outboundSyncId);
    }
}

