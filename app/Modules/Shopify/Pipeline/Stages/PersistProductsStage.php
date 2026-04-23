<?php

namespace App\Modules\Shopify\Pipeline\Stages;

use App\Modules\Shopify\Services\Sync\ProductSyncService;
use App\Modules\Stores\Models\Store;

class PersistProductsStage
{
    public function __construct(
        private readonly ProductSyncService $service
    ) {}

    public function handle(Store $store, array $products): array
    {
        $persisted = [];

        foreach ($products as $productData) {
            $persisted[] = $this->service->syncOne($store, $productData);
        }

        return $persisted;
    }
}
