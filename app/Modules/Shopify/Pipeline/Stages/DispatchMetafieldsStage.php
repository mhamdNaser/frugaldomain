<?php

namespace App\Modules\Shopify\Pipeline\Stages;

use App\Modules\Shopify\Jobs\SyncMetafieldsJob;
use App\Modules\Stores\Models\Store;

class DispatchMetafieldsStage
{
    public function handle(Store $store, array $products): void
    {
        foreach ($products as $product) {
            SyncMetafieldsJob::dispatch(
                $store->id,
                $product->id,
                $product->shopify_product_id
            )->onQueue('shopify-metafields');
        }
    }
}
