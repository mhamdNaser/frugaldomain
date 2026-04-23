<?php

namespace App\Modules\Shopify\Pipeline\Stages;

use App\Modules\Shopify\Jobs\SyncVariantImagesJob;
use App\Modules\Stores\Models\Store;

class DispatchVariantImagesStage
{
    public function handle(Store $store, array $variants): void
    {
        foreach ($variants as $variant) {
            SyncVariantImagesJob::dispatch(
                $store->id,
                $variant->id,
                $variant->shopify_variant_id
            )->onQueue('shopify-variant-images');
        }
    }
}
