<?php

namespace App\Modules\Shopify\Services\Sync;

use App\Modules\Catalog\Models\ProductType;
use App\Modules\Stores\Models\Store;
use Illuminate\Support\Str;

class ProductTypeSyncService
{

    public function sync(Store $store, $typeData): ?int
    {
        if (empty($typeData)) {
            return null;
        }

        $productType = ProductType::updateOrCreate(
            [
                'store_id' => $store->id,
                'slug' => Str::slug($typeData) ?? null,
            ],
            [
                'name' => $typeData ?? null,
            ]
        );

        return $productType->id;
    }
}
