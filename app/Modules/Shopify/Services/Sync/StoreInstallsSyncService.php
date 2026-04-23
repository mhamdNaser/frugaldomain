<?php

namespace App\Modules\Shopify\Services\Sync;

use App\Modules\Shopify\DTOs\StoreInstallData;
use App\Modules\Stores\Models\Store;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class StoreInstallsSyncService
{
    public function sync(Store $store): void
    {
        $shop = $this->fetchShop($store);
        $scopes = $this->fetchScopes($store);

        $data = new StoreInstallData(
            shop: (string) ($shop['myshopify_domain'] ?? $store->shopify_domain),
            state: 'installed',
            scopes: empty($scopes) ? null : implode(',', $scopes),
            shopifyStoreId: isset($shop['id']) ? (string) $shop['id'] : null,
            rawPayload: [
                'shop' => $shop,
                'scopes' => $scopes,
            ],
        );

        $existingId = DB::table('store_installs')
            ->where('store_id', $store->id)
            ->where('shop', $data->shop)
            ->whereNull('uninstalled_at')
            ->value('id');

        $payload = [
            'store_id' => $store->id,
            'shop' => $data->shop,
            'state' => $data->state,
            'scopes' => $data->scopes,
            'access_token' => $store->shopify_access_token,
            'token_created_at' => now(),
            'installed_at' => $store->installed_at ?? now(),
            'uninstalled_at' => $store->uninstalled_at,
            'updated_at' => now(),
        ];

        if ($this->hasColumn('store_installs', 'shopify_store_id')) {
            $payload['shopify_store_id'] = $data->shopifyStoreId;
        }

        if ($this->hasColumn('store_installs', 'raw_payload')) {
            $payload['raw_payload'] = json_encode($data->rawPayload);
        }

        if ($existingId) {
            DB::table('store_installs')->where('id', $existingId)->update($payload);

            return;
        }

        $payload['created_at'] = now();
        DB::table('store_installs')->insert($payload);
    }

    private function fetchShop(Store $store): array
    {
        $response = Http::withHeaders([
            'X-Shopify-Access-Token' => Crypt::decryptString($store->shopify_access_token),
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ])->get(sprintf(
            'https://%s/admin/api/2026-01/shop.json',
            $this->normalizeDomain($store->shopify_domain)
        ));

        if (!$response->successful()) {
            throw new RuntimeException('Failed to fetch shop data for store installs sync.');
        }

        return (array) $response->json('shop', []);
    }

    /**
     * @return array<int, string>
     */
    private function fetchScopes(Store $store): array
    {
        $response = Http::withHeaders([
            'X-Shopify-Access-Token' => Crypt::decryptString($store->shopify_access_token),
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ])->get(sprintf(
            'https://%s/admin/oauth/access_scopes.json',
            $this->normalizeDomain($store->shopify_domain)
        ));

        if (!$response->successful()) {
            return [];
        }

        $items = $response->json('access_scopes', []);

        if (!is_array($items)) {
            return [];
        }

        return collect($items)
            ->pluck('handle')
            ->filter(fn ($scope) => is_string($scope) && $scope !== '')
            ->values()
            ->all();
    }

    private function hasColumn(string $table, string $column): bool
    {
        static $cache = [];
        $key = $table . ':' . $column;

        if (array_key_exists($key, $cache)) {
            return $cache[$key];
        }

        return $cache[$key] = \Illuminate\Support\Facades\Schema::hasColumn($table, $column);
    }

    private function normalizeDomain(string $domain): string
    {
        $domain = trim($domain);
        $domain = preg_replace('#^https?://#', '', $domain) ?? $domain;

        return rtrim($domain, '/');
    }
}

