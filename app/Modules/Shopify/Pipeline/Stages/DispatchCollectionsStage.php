<?php

namespace App\Modules\Shopify\Pipeline\Stages;

use App\Modules\Shopify\Jobs\SyncCollectionsJob;
use App\Modules\Stores\Models\Store;

class DispatchCollectionsStage
{
    public function handle(Store $store, array $products): void
    {
        foreach ($products as $product) {
            SyncCollectionsJob::dispatch(
                $store->id,
                $product->id,
                $product->shopify_product_id
            )->onQueue('shopify-collections');
        }
    }
}
