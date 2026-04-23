<?php

namespace App\Modules\Shopify\Pipeline\Stages;

use App\Modules\Shopify\Jobs\SyncProductImagesJob;
use App\Modules\Stores\Models\Store;

class DispatchProductImagesStage
{
    public function handle(Store $store, array $products): void
    {
        foreach ($products as $product) {
            SyncProductImagesJob::dispatch(
                $store->id,
                $product->id,
                $product->shopify_product_id
            )->onQueue('shopify-images');
        }
    }
}
