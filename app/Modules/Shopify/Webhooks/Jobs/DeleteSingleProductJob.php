<?php

namespace App\Modules\Shopify\Webhooks\Jobs;

use App\Modules\Catalog\Models\Product;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class DeleteSingleProductJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 120;

    public function __construct(
        public readonly string $storeId,
        public readonly string $shopifyProductId,
        public readonly ?string $webhookExternalId = null,
    ) {
        $this->onQueue('shopify-sync');
    }

    public function handle(): void
    {
        $gid = str_starts_with($this->shopifyProductId, 'gid://')
            ? $this->shopifyProductId
            : 'gid://shopify/Product/' . $this->shopifyProductId;

        /** @var Product|null $product */
        $product = Product::query()
            ->where('store_id', $this->storeId)
            ->where(function ($q) use ($gid) {
                $q->where('shopify_product_id', $gid)
                    ->orWhere('shopify_product_id', $this->shopifyProductId);
            })
            ->first();

        if (!$product) {
            return;
        }

        DB::transaction(function () use ($product) {
            $product->variants()->delete();
            $product->collections()->detach();
            $product->options()->detach();
            $product->tags()->detach();
            $product->delete();
        });
    }
}

