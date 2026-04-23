<?php

namespace App\Modules\Shopify\Pipeline\Stages;

use App\Modules\Shopify\Jobs\SyncInventoryJob;
use App\Modules\Stores\Models\Store;

class DispatchInventoryStage
{
    public function handle(Store $store, array $products): void
    {
        foreach ($products as $product) {
            SyncInventoryJob::dispatch(
                $store->id,
                $product->id,
                $product->shopify_product_id
            )->onQueue('shopify-inventory');
        }
    }
}
