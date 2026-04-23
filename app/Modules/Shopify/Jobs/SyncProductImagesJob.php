<?php

namespace App\Modules\Shopify\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Modules\Catalog\Models\Product;
use App\Modules\Shopify\Services\Sync\ImageProductSyncService;
use App\Modules\Stores\Models\Store;

class SyncProductImagesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public readonly string $storeId,
        public readonly int $productId,
        public readonly string $shopifyProductId,
    ) {
        $this->onQueue('shopify-images');
    }

    public function handle(ImageProductSyncService $service): void
    {
        $store = Store::findOrFail($this->storeId);
        $product = Product::findOrFail($this->productId);

        $service->syncByProduct(
            $store,
            $product,
            $this->shopifyProductId
        );
    }
}
