<?php

namespace App\Modules\Stores\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Checks a Shopify store domain + Admin API access token against Shopify
 * itself, before anything is saved: is the token valid, which shop is it,
 * and does it carry the scopes the sync jobs need.
 */
class ShopifyConnectionService
{
    public const API_VERSION = '2026-04';

    /** Scopes the import (sync jobs) cannot work without. */
    public const REQUIRED_SCOPES = [
        'read_products',
        'read_inventory',
        'read_locations',
        'read_orders',
        'read_customers',
    ];

    /** Scopes that unlock the remaining sections; missing ones only limit what is imported. */
    public const RECOMMENDED_SCOPES = [
        'read_draft_orders',
        'read_fulfillments',
        'read_discounts',
        'read_shipping',
        'read_content',
        'read_online_store_navigation',
        'read_files',
        'read_markets',
        'read_returns',
        'read_metaobjects',
        'read_themes',
        'write_products',
        'write_inventory',
    ];

    /**
     * "https://My-Store.myshopify.com/admin" -> "my-store.myshopify.com".
     * Only *.myshopify.com domains are accepted, so the token is never sent
     * anywhere but Shopify.
     */
    public function normalizeDomain(string $domain): string
    {
        $domain = Str::lower(trim($domain));
        $domain = preg_replace('#^https?://#', '', $domain);
        $domain = explode('/', $domain)[0];

        if (! preg_match('/^[a-z0-9][a-z0-9-]*\.myshopify\.com$/', $domain)) {
            throw new RuntimeException('Enter your store domain ending in .myshopify.com (for example my-store.myshopify.com).');
        }

        return $domain;
    }

    /**
     * @return array{shop: array, scopes: string[], missing_required: string[], missing_recommended: string[]}
     */
    public function verify(string $domain, string $accessToken): array
    {
        $domain = $this->normalizeDomain($domain);

        $shopResponse = $this->request($domain, $accessToken, "/admin/api/" . self::API_VERSION . "/shop.json");

        if ($shopResponse->status() === 401 || $shopResponse->status() === 403) {
            throw new RuntimeException('Shopify rejected the access token. Check that you copied the Admin API access token (it starts with shpat_) for this store.');
        }
        if ($shopResponse->status() === 404) {
            throw new RuntimeException('No Shopify store was found at this domain.');
        }
        if (! $shopResponse->successful() || ! is_array($shopResponse->json('shop'))) {
            throw new RuntimeException('Shopify did not answer as expected (HTTP ' . $shopResponse->status() . '). Try again in a moment.');
        }

        $scopesResponse = $this->request($domain, $accessToken, '/admin/oauth/access_scopes.json');
        $scopes = collect($scopesResponse->json('access_scopes') ?? [])->pluck('handle')->filter()->values()->all();

        $shop = $shopResponse->json('shop');

        return [
            'shop' => [
                'id' => $shop['id'] ?? null,
                'name' => $shop['name'] ?? null,
                'email' => $shop['email'] ?? null,
                'domain' => $shop['myshopify_domain'] ?? $domain,
                'primary_domain' => $shop['domain'] ?? null,
                'currency' => $shop['currency'] ?? null,
                'timezone' => $shop['iana_timezone'] ?? null,
                'plan_name' => $shop['plan_display_name'] ?? $shop['plan_name'] ?? null,
                'shop_owner' => $shop['shop_owner'] ?? null,
                'country' => $shop['country_name'] ?? null,
            ],
            'scopes' => $scopes,
            'missing_required' => $this->missing(self::REQUIRED_SCOPES, $scopes),
            'missing_recommended' => $this->missing(self::RECOMMENDED_SCOPES, $scopes),
        ];
    }

    private function request(string $domain, string $accessToken, string $path)
    {
        try {
            return Http::withHeaders([
                'X-Shopify-Access-Token' => $accessToken,
                'Accept' => 'application/json',
            ])->timeout(15)->get("https://{$domain}{$path}");
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            throw new RuntimeException('Could not reach Shopify at this domain. Check the domain and try again.');
        }
    }

    /** A write_x scope implies read_x on Shopify. */
    private function missing(array $wanted, array $granted): array
    {
        return array_values(array_filter($wanted, function ($scope) use ($granted) {
            if (in_array($scope, $granted, true)) {
                return false;
            }

            return ! (str_starts_with($scope, 'read_') && in_array('write_' . substr($scope, 5), $granted, true));
        }));
    }
}
