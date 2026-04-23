<?php

namespace App\Modules\Shopify\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Shopify\Jobs\Pagination\SyncProductsPaginationOrchestratorJob;
use App\Modules\Shopify\Services\Sync\SyncRunTracker;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Bus;
use Throwable;

class ShopifyProductsSyncController extends Controller
{
    public function __construct(
        private readonly SyncRunTracker $tracker,
    ) {}

    public function syncProducts(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            if (!$user) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Unauthenticated.',
                ], 401);
            }

            $store = $user->store()->first();

            if (!$store) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'No store is linked to the authenticated user.',
                ], 404);
            }

            $perPage = (int) $request->input('per_page', 20);
            $perPage = min(max($perPage, 5), 100);
            $syncRunId = $this->tracker->startRun((string) $store->id, 'products');

            $job = new SyncProductsPaginationOrchestratorJob(
                storeId: (string) $store->id,
                syncRunId: $syncRunId,
                first: $perPage,
                after: null
            );

            $batch = Bus::batch([$job])
                ->name("sync-store-products:{$store->id}")
                ->dispatch();

            $this->tracker->attachBatch($syncRunId, $batch->id);

            return response()->json([
                'status' => 'success',
                'message' => 'Products sync started successfully.',
                'data' => [
                    'sync_run_id' => $syncRunId,
                    'batch_id' => $batch->id,
                    'store_id' => $store->id,
                    'per_page' => $perPage,
                ],
            ], 202);
        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'status' => 'error',
                'message' => 'Server Error',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}