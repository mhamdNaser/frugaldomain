<?php

namespace App\Modules\MobileApp\Actions;

use App\Modules\Stores\Models\Store;

class ResolveMobileStoreAction
{
    public function execute(string $storeId): Store
    {
        $store = Store::query()
            ->where('id', $storeId)
            ->whereNull('deleted_at')
            ->first();

        abort_if(!$store, 404, 'Store not found.');
        abort_if((string) $store->status !== 'active', 422, 'Store is not active.');

        return $store;
    }
}
