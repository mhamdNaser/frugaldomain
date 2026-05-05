<?php

namespace App\Modules\Stores\Repositories\Eloquent;

use App\Modules\Stores\Repositories\Interfaces\AccountsManageRepositoryInterface;
use Illuminate\Support\Facades\DB;

class AccountsManageRepository implements AccountsManageRepositoryInterface
{
    public function forPartner(string $storeId): array
    {
        $store = DB::table('stores')->where('id', $storeId)->first();
        $settings = DB::table('store_settings')->where('store_id', $storeId)->first();
        $branding = DB::table('store_brandings')->where('store_id', $storeId)->first();
        $shopifyStore = DB::table('shopify_stores')->where('store_id', $storeId)->first();

        return [
            'store' => $store ? (array) $store : null,
            'settings' => $settings ? (array) $settings : null,
            'branding' => $branding ? (array) $branding : null,
            'shopify' => $shopifyStore ? (array) $shopifyStore : null,
        ];
    }
}

