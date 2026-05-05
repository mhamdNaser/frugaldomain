<?php

namespace App\Modules\Shopify\OutboundSync\Contracts;

use App\Modules\Shopify\OutboundSync\DTOs\OutboundSyncOperation;
use App\Modules\Shopify\OutboundSync\DTOs\OutboundSyncResult;
use App\Modules\Stores\Models\Store;

interface OutboundSyncHandlerInterface
{
    public function handle(Store $store, OutboundSyncOperation $operation): OutboundSyncResult;
}

