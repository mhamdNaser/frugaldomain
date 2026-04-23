<?php

namespace App\Modules\Shopify\Services\Sync;

use App\Modules\Shopify\DTOs\ShopifyStoreSnapshotData;
use App\Modules\Stores\Models\ShopifyStore;
use App\Modules\Stores\Models\Store;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class ShopifyStoreDetailsSyncService
{
    public function sync(Store $store): ShopifyStore
    {
        $shop = $this->fetchShop($store);
        $data = $this->map($shop);

        return ShopifyStore::query()->updateOrCreate(
            [
                'shopify_store_id' => $data->shopifyStoreId,
            ],
            [
                'store_id' => $store->id,
                'name' => $data->name,
                'email' => $data->email,
                'domain' => $data->domain,
                'myshopify_domain' => $data->myshopifyDomain,
                'shop_owner' => $data->shopOwner,
                'phone' => $data->phone,
                'country' => $data->country,
                'country_code' => $data->countryCode,
                'currency' => $data->currency,
                'timezone' => $data->timezone,
                'iana_timezone' => $data->ianaTimezone,
                'plan_name' => $data->planName,
                'plan_display_name' => $data->planDisplayName,
                'taxes_included' => $data->taxesIncluded,
                'county_taxes' => $data->countyTaxes,
                'has_discounts' => $data->hasDiscounts,
                'has_gift_cards' => $data->hasGiftCards,
                'multi_location_enabled' => $data->multiLocationEnabled,
                'primary_location_id' => $data->primaryLocationId,
                'raw_data' => $data->rawData,
            ]
        );
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
            throw new RuntimeException('Failed to fetch Shopify shop details for sync.');
        }

        return (array) $response->json('shop', []);
    }

    private function map(array $shop): ShopifyStoreSnapshotData
    {
        return new ShopifyStoreSnapshotData(
            shopifyStoreId: (string) ($shop['id'] ?? ''),
            name: $shop['name'] ?? null,
            email: $shop['email'] ?? null,
            domain: $shop['domain'] ?? null,
            myshopifyDomain: $shop['myshopify_domain'] ?? null,
            shopOwner: $shop['shop_owner'] ?? null,
            phone: $shop['phone'] ?? null,
            country: $shop['country_name'] ?? ($shop['country'] ?? null),
            countryCode: $shop['country_code'] ?? null,
            currency: $shop['currency'] ?? null,
            timezone: $shop['timezone'] ?? null,
            ianaTimezone: $shop['iana_timezone'] ?? null,
            planName: $shop['plan_name'] ?? null,
            planDisplayName: $shop['plan_display_name'] ?? null,
            taxesIncluded: (bool) ($shop['taxes_included'] ?? false),
            countyTaxes: (bool) ($shop['county_taxes'] ?? false),
            hasDiscounts: (bool) ($shop['has_discounts'] ?? false),
            hasGiftCards: (bool) ($shop['has_gift_cards'] ?? false),
            multiLocationEnabled: (bool) ($shop['multi_location_enabled'] ?? false),
            primaryLocationId: isset($shop['primary_location_id']) ? (int) $shop['primary_location_id'] : null,
            rawData: $shop,
        );
    }

    private function normalizeDomain(string $domain): string
    {
        $domain = trim($domain);
        $domain = preg_replace('#^https?://#', '', $domain) ?? $domain;

        return rtrim($domain, '/');
    }
}
