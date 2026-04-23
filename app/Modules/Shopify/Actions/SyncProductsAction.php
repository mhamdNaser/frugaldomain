<?php

namespace App\Modules\Shopify\Actions;

use App\Modules\Shopify\Pipeline\ProductSyncPipeline;
use App\Modules\Stores\Models\Store;

class SyncProductsAction
{
    public function __construct(
        private readonly ProductSyncPipeline $pipeline,
    ) {}

    public function execute(Store $store, int $first = 20, ?string $after = null): array
    {
        return $this->pipeline->run($store, $first, $after);
    }
}
