<?php

namespace App\Modules\User\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\User\Repositories\Interfaces\CustomerRepositoryInterface;
use App\Modules\User\Requests\Customer\CustomersIndexRequest;
use App\Modules\User\Requests\Customer\UpdateCustomerRequest;
use App\Modules\User\Resources\CustomerDetailResource;
use App\Modules\User\Resources\CustomerTableResource;
use App\Modules\Shopify\OutboundSync\Services\LocalChangeOutboundSyncDispatcher;
use App\Modules\Shopify\OutboundSync\Services\ShopifyFirstSyncService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function __construct(
        protected CustomerRepositoryInterface $repo,
        protected LocalChangeOutboundSyncDispatcher $outboundSyncDispatcher,
        protected ShopifyFirstSyncService $shopifyFirstSyncService,
    ) {}

    public function index(CustomersIndexRequest $request): JsonResponse
    {
        $data = $request->validated();
        $search = $data['search'] ?? null;
        $rowsPerPage = (int) ($data['rowsPerPage'] ?? 10);
        $customers = $this->repo->getAllByStore($this->resolveStoreId($request), $search, $rowsPerPage);

        return response()->json([
            'data' => CustomerTableResource::collection($customers->items()),
            'meta' => [
                'total' => $customers->total(),
                'per_page' => $customers->perPage(),
                'current_page' => $customers->currentPage(),
                'last_page' => $customers->lastPage(),
                'from' => $customers->firstItem(),
                'to' => $customers->lastItem(),
            ],
            'links' => [
                'first' => $customers->url(1),
                'last' => $customers->url($customers->lastPage()),
                'prev' => $customers->previousPageUrl(),
                'next' => $customers->nextPageUrl(),
            ],
        ]);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $customer = $this->repo->findForStoreWithDetails($this->resolveStoreId($request), $id);

        if (!$customer) {
            return response()->json(['message' => 'Customer not found'], 404);
        }

        return response()->json([
            'data' => new CustomerDetailResource($customer),
        ]);
    }

    public function store(UpdateCustomerRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $storeId = $this->resolveStoreId($request);
        $shopifyExecuted = $this->shopifyFirstSyncService->syncOrFail($validated, $storeId);
        $customer = $this->repo->createForStore($storeId, $validated);
        $outboundSyncId = $shopifyExecuted ? null : $this->outboundSyncDispatcher->dispatchFromValidated(
            validated: $validated,
            storeId: (string) $customer->store_id,
            entityType: 'customer',
            entityId: (string) $customer->id,
            action: 'create',
        );

        return response()->json([
            'message' => 'Customer created successfully',
            'data' => new CustomerDetailResource($customer),
            'meta' => [
                'outbound_sync_id' => $outboundSyncId,
            ],
        ], 201);
    }

    public function update(UpdateCustomerRequest $request, int $id): JsonResponse
    {
        $validated = $request->validated();
        $storeId = $this->resolveStoreId($request);
        $shopifyExecuted = $this->shopifyFirstSyncService->syncOrFail($validated, $storeId);
        $customer = $this->repo->updateForStore($storeId, $id, $validated);
        $outboundSyncId = $shopifyExecuted ? null : $this->outboundSyncDispatcher->dispatchFromValidated(
            validated: $validated,
            storeId: (string) $customer->store_id,
            entityType: 'customer',
            entityId: (string) $customer->id,
            action: 'update',
        );

        return response()->json([
            'message' => 'Customer updated successfully',
            'data' => new CustomerDetailResource($customer),
            'meta' => [
                'outbound_sync_id' => $outboundSyncId,
            ],
        ]);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'shopify_sync' => ['sometimes', 'array'],
            'shopify_sync.mutation' => ['sometimes', 'required_without:shopify_sync.query', 'string'],
            'shopify_sync.query' => ['sometimes', 'required_without:shopify_sync.mutation', 'string'],
            'shopify_sync.variables' => ['nullable', 'array'],
            'shopify_sync.resource_path' => ['nullable', 'string', 'max:255'],
            'shopify_sync.user_errors_path' => ['nullable', 'string', 'max:255'],
            'shopify_sync.idempotency_key' => ['nullable', 'string', 'max:255'],
            'shopify_sync.correlation_id' => ['nullable', 'string', 'max:255'],
            'shopify_sync.priority' => ['nullable', 'integer', 'min:0', 'max:9'],
            'shopify_sync.max_attempts' => ['nullable', 'integer', 'min:1', 'max:20'],
        ]);

        $storeId = $this->resolveStoreId($request);
        $customer = $this->repo->findForStoreWithDetails($storeId, $id);

        if (!$customer) {
            return response()->json(['message' => 'Customer not found'], 404);
        }

        $entityId = (string) $customer->id;
        $shopifyExecuted = $this->shopifyFirstSyncService->syncOrFail($validated, (string) $storeId);
        $this->repo->deleteForStore($storeId, $id);

        $outboundSyncId = $shopifyExecuted ? null : $this->outboundSyncDispatcher->dispatchFromValidated(
            validated: $validated,
            storeId: (string) $storeId,
            entityType: 'customer',
            entityId: $entityId,
            action: 'delete',
        );

        return response()->json([
            'message' => 'Customer deleted successfully',
            'meta' => [
                'outbound_sync_id' => $outboundSyncId,
            ],
        ]);
    }

    private function resolveStoreId(Request $request): string
    {
        $user = $request->user();
        abort_if(!$user, 401, 'Unauthenticated.');

        $store = $user->store()->first();
        abort_if(!$store, 404, 'No store is linked to the authenticated user.');

        return (string) $store->id;
    }
}
