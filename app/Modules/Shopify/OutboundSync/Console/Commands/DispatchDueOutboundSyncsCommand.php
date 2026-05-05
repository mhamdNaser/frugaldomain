<?php

namespace App\Modules\Shopify\OutboundSync\Console\Commands;

use App\Modules\Shopify\OutboundSync\Jobs\DispatchDueOutboundSyncsJob;
use Illuminate\Console\Command;

class DispatchDueOutboundSyncsCommand extends Command
{
    protected $signature = 'shopify:outbound-dispatch-due {--limit=100 : Maximum due operations to dispatch}';
    protected $description = 'Dispatch due Shopify outbound sync operations.';

    public function handle(): int
    {
        $limit = (int) $this->option('limit');
        $limit = max(1, min(1000, $limit));

        DispatchDueOutboundSyncsJob::dispatch($limit);

        $this->info("Queued due outbound sync dispatcher with limit={$limit}.");

        return self::SUCCESS;
    }
}

