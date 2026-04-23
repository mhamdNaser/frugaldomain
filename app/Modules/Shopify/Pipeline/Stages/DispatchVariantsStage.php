<?php

namespace App\Modules\Shopify\Pipeline\Stages;

use App\Modules\Shopify\Jobs\SyncProductVariantsJob;
use App\Modules\Stores\Models\Store;

class DispatchVariantsStage
{
    public function handle(Store $store, array $products): void
    {
        foreach ($products as $product) {
            SyncProductVariantsJob::dispatch(
                $store->id,
                $product->id,
                $product->shopify_product_id
            );
        }
    }
}
