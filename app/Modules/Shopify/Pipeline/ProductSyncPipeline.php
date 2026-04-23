<?php

namespace App\Modules\Shopify\Pipeline;

use App\Modules\Shopify\Pipeline\Stages\FetchProductsStage;
use App\Modules\Shopify\Pipeline\Stages\NormalizeProductsStage;
use App\Modules\Shopify\Pipeline\Stages\PersistProductsStage;
use App\Modules\Shopify\Pipeline\Stages\DispatchVariantsStage;
use App\Modules\Shopify\Pipeline\Stages\DispatchProductImagesStage;
use App\Modules\Shopify\Pipeline\Stages\DispatchInventoryStage;
use App\Modules\Shopify\Pipeline\Stages\DispatchMetafieldsStage;
use App\Modules\Shopify\Pipeline\Stages\DispatchCollectionsStage;
use App\Modules\Stores\Models\Store;

class ProductSyncPipeline
{
    public function __construct(
        private readonly FetchProductsStage $fetch,
        private readonly NormalizeProductsStage $normalize,
        private readonly PersistProductsStage $persist,
        private readonly DispatchVariantsStage $variants,
        private readonly DispatchProductImagesStage $images,
        private readonly DispatchInventoryStage $inventory,
        private readonly DispatchMetafieldsStage $metafields,
        private readonly DispatchCollectionsStage $collections,
    ) {}

    public function run(Store $store, int $first, ?string $after): array
    {
        $raw = $this->fetch->handle($store, $first, $after);
        $normalized = $this->normalize->handle($raw);
        $persisted = $this->persist->handle($store, $normalized);

        $this->variants->handle($store, $persisted);
        $this->images->handle($store, $persisted);
        $this->inventory->handle($store, $persisted);
        $this->metafields->handle($store, $persisted);
        $this->collections->handle($store, $persisted);

        return [
            'products' => $persisted,
            'page_info' => $raw['pageInfo'],
        ];
    }
}
