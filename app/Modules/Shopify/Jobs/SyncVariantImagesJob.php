<?php

namespace App\Modules\Shopify\Jobs;

use App\Modules\Catalog\Models\ProductVariant;
use App\Modules\Shopify\Services\Sync\ImageVariantSyncService;
use App\Modules\Stores\Models\Store;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SyncVariantImagesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public readonly string $storeId,
        public readonly int $variantId,
        public readonly string $shopifyVariantId,
    ) {
        $this->onQueue('shopify-variant-images');
    }

    public function handle(ImageVariantSyncService $service): void
    {
        $store = Store::findOrFail($this->storeId);
        $variant = ProductVariant::findOrFail($this->variantId);

        $service->syncByVariant(
            $store,
            $variant,
            $this->shopifyVariantId
        );
    }
}
