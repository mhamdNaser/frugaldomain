<?php

namespace App\Modules\Shopify\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Shopify\Requests\UpdateShopifyWebhookSecretRequest;
use Illuminate\Http\JsonResponse;

class ShopifyWebhookSecretController extends Controller
{
    public function update(UpdateShopifyWebhookSecretRequest $request): JsonResponse
    {
        $user = $request->user();
        $store = $user?->store()->first();

        if (!$store) {
            return response()->json([
                'status' => 'error',
                'message' => 'No store is linked to the authenticated user.',
            ], 404);
        }

        $store->update([
            'shopify_webhook_secret' => (string) $request->input('shopify_webhook_secret'),
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Shopify webhook secret updated successfully.',
        ]);
    }
}

