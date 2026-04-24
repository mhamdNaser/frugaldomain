<?php

namespace App\Modules\Shopify\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\User\Models\User;
use App\Modules\Stores\Models\ShopifyStore;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\JsonResponse;

class StoreConnectionController extends Controller
{
    public function status(string $id): JsonResponse
    {
        try {
            $shopData = $this->connectionStory($id);

            return response()->json([
                'status' => 'success',
                'connected' => true,
                'message' => 'Store connected successfully',
                'shopify_api' => [
                    'reachable' => true,
                    'shop_name' => $shopData['name'] ?? null,
                    'shop_email' => $shopData['email'] ?? null,
                    'shop_currency' => $shopData['currency'] ?? null,
                    'shop_owner' => $shopData['shop_owner'] ?? null,
                    'plan_name' => $shopData['plan_name'] ?? null,
                    'domain' => $shopData['domain'] ?? null,
                ],
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => 'error',
                'connected' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    public function SaveShopifyStoreDetails(string $id): JsonResponse
    {
        try {
            $shopData = $this->connectionStory($id);

            $user = User::with('store')->find($id);

            if (!$user) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'User not found',
                ], 404);
            }

            $store = $user->store;

            if (!$store || !$store->shopify_domain || !$store->shopify_access_token) {
                return response()->json([
                    'status' => 'error',
                    'connected' => false,
                    'message' => 'Store missing Shopify credentials',
                ], 422);
            }

            ShopifyStore::updateOrCreate(
                ['shopify_store_id' => $shopData['id']],
                [
                    'store_id' => $store->id,
                    'name' => $shopData['name'] ?? null,
                    'email' => $shopData['email'] ?? null,
                    'domain' => $shopData['domain'] ?? null,
                    'myshopify_domain' => $shopData['myshopify_domain'] ?? null,
                    'shop_owner' => $shopData['shop_owner'] ?? null,
                    'phone' => $shopData['phone'] ?? null,
                    'country' => $shopData['country'] ?? null,
                    'country_code' => $shopData['country_code'] ?? null,
                    'currency' => $shopData['currency'] ?? null,
                    'timezone' => $shopData['timezone'] ?? null,
                    'iana_timezone' => $shopData['iana_timezone'] ?? null,
                    'plan_name' => $shopData['plan_name'] ?? null,
                    'plan_display_name' => $shopData['plan_display_name'] ?? null,
                    'taxes_included' => $shopData['taxes_included'] ?? false,
                    'county_taxes' => $shopData['county_taxes'] ?? false,
                    'has_discounts' => $shopData['has_discounts'] ?? false,
                    'has_gift_cards' => $shopData['has_gift_cards'] ?? false,
                    'multi_location_enabled' => $shopData['multi_location_enabled'] ?? false,
                    'primary_location_id' => $shopData['primary_location_id'] ?? null,
                    'raw_data' => json_encode($shopData),
                ]
            );

            return response()->json([
                'status' => 'success',
                'message' => 'Shopify store details saved successfully',
                'store' => [
                    'shopify_domain' => $store->shopify_domain,
                ],
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    private function connectionStory(string $id): array
    {
        $user = User::with('store')->find($id);

        if (!$user) {
            throw new \RuntimeException('User not found');
        }

        $store = $user->store;

        if (!$store || !$store->shopify_domain || !$store->shopify_access_token) {
            throw new \RuntimeException('Store missing Shopify credentials');
        }

        $response = Http::withHeaders([
            'X-Shopify-Access-Token' => Crypt::decryptString($store->shopify_access_token),
            'Content-Type' => 'application/json',
        ])->get("https://{$store->shopify_domain}/admin/api/2024-01/shop.json");

        if (!$response->successful()) {
            throw new \RuntimeException(
                'Failed to connect to Shopify API. Status code: ' . $response->status()
            );
        }

        $shopData = $response->json('shop');

        if (!is_array($shopData)) {
            throw new \RuntimeException('Invalid Shopify response structure');
        }

        return $shopData;
    }
}
