<?php

namespace App\Modules\Shopify\OutboundSync\Actions;

use App\Modules\Shopify\OutboundSync\DTOs\EnqueueOutboundSyncData;
use App\Modules\Shopify\OutboundSync\Services\OutboundSyncManager;

class QueueOutboundSyncAction
{
    public function __construct(
        private readonly OutboundSyncManager $manager,
    ) {}

    public function execute(EnqueueOutboundSyncData $data): int
    {
        return $this->manager->enqueueAndDispatch($data);
    }
}

