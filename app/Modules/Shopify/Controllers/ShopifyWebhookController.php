<?php

namespace App\Modules\Shopify\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Shopify\Webhooks\Jobs\ProcessShopifyWebhookJob;
use App\Modules\Shopify\Webhooks\Services\ShopifyWebhookLogger;
use App\Modules\Shopify\Webhooks\Services\ShopifyWebhookPayloadParser;
use App\Modules\Shopify\Webhooks\Services\ShopifyWebhookVerifier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ShopifyWebhookController extends Controller
{
    public function handle(
        Request $request,
        ShopifyWebhookPayloadParser $parser,
        ShopifyWebhookVerifier $verifier,
        ShopifyWebhookLogger $logger,
    ): JsonResponse {
        $rawBody = (string) $request->getContent();

        $data = $parser->parse($request, $rawBody);

        if (!$verifier->verify($rawBody, $data->hmacHeader, $data->storeId)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid Shopify webhook signature.',
            ], 401);
        }

        $log = $logger->log($data);

        if ($log->status === 'processed' || $log->status === 'processing') {
            return response()->json(['status' => 'ok']);
        }

        ProcessShopifyWebhookJob::dispatch($log->id);

        return response()->json(['status' => 'ok']);
    }
}
