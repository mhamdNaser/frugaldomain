<?php

namespace App\Modules\Shopify\OutboundSync\Services;

use App\Modules\Shopify\OutboundSync\Support\ArrayPath;
use App\Modules\Shopify\Services\ShopifyClient;
use App\Modules\Stores\Models\Store;
use Illuminate\Validation\ValidationException;

class ShopifyFirstSyncService
{
    /**
     * @param array<string, mixed> $validated
     */
    public function syncOrFail(array $validated, string $storeId): bool
    {
        $shopifySync = $validated['shopify_sync'] ?? null;

        if (!is_array($shopifySync)) {
            return false;
        }

        $payload = is_array($shopifySync['payload'] ?? null) ? $shopifySync['payload'] : [];
        $query = $payload['query'] ?? $shopifySync['query'] ?? null;
        $mutation = $payload['mutation'] ?? $shopifySync['mutation'] ?? null;
        $variables = $payload['variables'] ?? $shopifySync['variables'] ?? [];
        $userErrorsPath = $payload['user_errors_path'] ?? $shopifySync['user_errors_path'] ?? null;

        $graphql = is_string($mutation) ? $mutation : (is_string($query) ? $query : null);

        if (!$graphql) {
            return false;
        }

        $store = Store::query()->find($storeId);
        abort_if(!$store, 404, 'Store not found.');

        $response = (new ShopifyClient($store))->query(
            $graphql,
            is_array($variables) ? $variables : []
        );

        if (is_string($userErrorsPath) && $userErrorsPath !== '') {
            $userErrors = ArrayPath::get($response, $userErrorsPath, []);

            if (is_array($userErrors) && $userErrors !== []) {
                $first = $userErrors[0] ?? [];
                $message = is_array($first) ? (string) ($first['message'] ?? 'Shopify user error.') : 'Shopify user error.';

                throw ValidationException::withMessages([
                    'shopify_sync' => $message,
                ]);
            }
        }

        return true;
    }
}
