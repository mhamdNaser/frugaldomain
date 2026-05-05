<?php

namespace App\Modules\Shopify\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Shopify\OutboundSync\DTOs\EnqueueOutboundSyncData;
use App\Modules\Shopify\OutboundSync\Enums\OutboundSyncStatus;
use App\Modules\Shopify\OutboundSync\Handlers\GenericGraphqlOutboundSyncHandler;
use App\Modules\Shopify\OutboundSync\Jobs\DispatchDueOutboundSyncsJob;
use App\Modules\Shopify\OutboundSync\Services\OutboundSyncManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ShopifyOutboundSyncController extends Controller
{
    public function __construct(
        private readonly OutboundSyncManager $manager,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $store = $this->store($request);
        $perPage = max(1, min(100, (int) $request->input('per_page', 20)));

        $query = DB::table('shopify_outbound_syncs')
            ->where('store_id', $store->id)
            ->when($request->filled('status'), fn ($q) => $q->where('status', (string) $request->string('status')))
            ->when($request->filled('entity_type'), fn ($q) => $q->where('entity_type', (string) $request->string('entity_type')))
            ->when($request->filled('action'), fn ($q) => $q->where('action', (string) $request->string('action')))
            ->orderBy('id', 'desc');

        $paginated = $query->paginate($perPage);

        return response()->json([
            'status' => 'success',
            'data' => $paginated,
        ]);
    }

    public function queue(Request $request): JsonResponse
    {
        $store = $this->store($request);

        $validated = $request->validate([
            'entity_type' => ['required', 'string', 'max:255'],
            'entity_id' => ['required', 'string', 'max:255'],
            'action' => ['required', 'string', 'max:100'],
            'payload' => ['required', 'array'],
            'payload.mutation' => ['required_without:payload.query', 'string'],
            'payload.query' => ['required_without:payload.mutation', 'string'],
            'payload.variables' => ['nullable', 'array'],
            'payload.user_errors_path' => ['nullable', 'string'],
            'payload.resource_path' => ['nullable', 'string'],
            'idempotency_key' => ['nullable', 'string', 'max:255'],
            'correlation_id' => ['nullable', 'string', 'max:255'],
            'priority' => ['nullable', 'integer', 'min:0', 'max:9'],
            'max_attempts' => ['nullable', 'integer', 'min:1', 'max:20'],
        ]);

        $id = $this->manager->enqueueAndDispatch(
            new EnqueueOutboundSyncData(
                storeId: (string) $store->id,
                entityType: $validated['entity_type'],
                entityId: $validated['entity_id'],
                action: $validated['action'],
                handler: GenericGraphqlOutboundSyncHandler::class,
                payload: $validated['payload'],
                idempotencyKey: $validated['idempotency_key'] ?? null,
                correlationId: $validated['correlation_id'] ?? null,
                priority: (int) ($validated['priority'] ?? 5),
                maxAttempts: (int) ($validated['max_attempts'] ?? 5),
            )
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Outbound sync operation queued.',
            'data' => [
                'outbound_sync_id' => $id,
            ],
        ], 202);
    }

    public function retry(Request $request, int $id): JsonResponse
    {
        $store = $this->store($request);
        $ok = $this->manager->retry($id, (string) $store->id);

        if (!$ok) {
            return response()->json([
                'status' => 'error',
                'message' => 'Retry is only allowed for failed/dead operations that belong to this store.',
            ], 422);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Outbound sync operation re-queued.',
            'data' => [
                'outbound_sync_id' => $id,
            ],
        ], 202);
    }

    public function dispatchDue(Request $request): JsonResponse
    {
        $store = $this->store($request);
        $limit = max(1, min(500, (int) $request->input('limit', 100)));

        $ids = DB::table('shopify_outbound_syncs')
            ->where('store_id', $store->id)
            ->whereIn('status', [OutboundSyncStatus::PENDING, OutboundSyncStatus::RETRYING])
            ->where(function ($query) {
                $query->whereNull('available_at')
                    ->orWhere('available_at', '<=', now());
            })
            ->orderBy('priority')
            ->orderBy('id')
            ->limit($limit)
            ->pluck('id');

        foreach ($ids as $operationId) {
            $this->manager->dispatch((int) $operationId);
        }

        if (count($ids) === 0) {
            DispatchDueOutboundSyncsJob::dispatch($limit);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Due outbound sync operations dispatched.',
            'data' => [
                'count' => count($ids),
                'ids' => $ids->values()->all(),
            ],
        ], 202);
    }

    private function store(Request $request)
    {
        $user = $request->user();

        abort_if(!$user, 401, 'Unauthenticated.');

        $store = $user->store()->first();

        abort_if(!$store, 404, 'No store is linked to the authenticated user.');

        return $store;
    }
}
