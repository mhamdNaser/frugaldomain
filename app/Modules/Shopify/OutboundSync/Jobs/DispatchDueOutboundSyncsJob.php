<?php

namespace App\Modules\Shopify\OutboundSync\Jobs;

use App\Modules\Shopify\OutboundSync\Enums\OutboundSyncStatus;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class DispatchDueOutboundSyncsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;
    public int $timeout = 120;

    public function __construct(
        public readonly int $limit = 100,
    ) {}

    public function handle(): void
    {
        $ids = DB::table('shopify_outbound_syncs')
            ->whereIn('status', [OutboundSyncStatus::PENDING, OutboundSyncStatus::RETRYING])
            ->where(function ($query) {
                $query->whereNull('available_at')
                    ->orWhere('available_at', '<=', now());
            })
            ->orderBy('priority')
            ->orderBy('id')
            ->limit(max(1, $this->limit))
            ->pluck('id');

        foreach ($ids as $id) {
            ProcessOutboundSyncJob::dispatch((int) $id);
        }
    }
}

