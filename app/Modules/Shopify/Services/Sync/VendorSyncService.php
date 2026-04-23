<?php

namespace App\Modules\Shopify\Services\Sync;

use App\Modules\Catalog\Models\Vendor;
use App\Modules\Stores\Models\Store;
use Illuminate\Support\Str;

class VendorSyncService
{

    public function sync(Store $store, $vendorData): ?int
    {
        if (empty($vendorData)) {
            return null;
        }

        $vendor = Vendor::updateOrCreate(
            [
                'store_id' => $store->id,
                'slug' => Str::slug($vendorData),
            ],
            [
                'name' => $vendorData,
            ]
        );

        return $vendor->id;
    }
}
