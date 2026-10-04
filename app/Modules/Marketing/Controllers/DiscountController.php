<?php

namespace App\Modules\Marketing\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Marketing\Repositories\Interfaces\DiscountsRepositoryInterface;
use App\Modules\Marketing\Requests\DiscountsIndexRequest;
use App\Modules\Marketing\Requests\UpdateDiscountRequest;
use App\Modules\Marketing\Resources\DiscountTableResource;
use App\Modules\Shopify\AutoSync\ShopifyAutoSync;
use App\Modules\Shopify\OutboundSync\Services\LocalChangeOutboundSyncDispatcher;
use App\Modules\Shopify\OutboundSync\Services\ShopifyFirstSyncService;
use Illuminate\Support\Arr;

class DiscountController extends Controller
{
    public function __construct(
        protected DiscountsRepositoryInterface $repo,
        protected LocalChangeOutboundSyncDispatcher $outboundSyncDispatcher,
        protected ShopifyAutoSync $shopifySync,
        protected ShopifyFirstSyncService $shopifyFirstSyncService,
    ) {}

    public function index(DiscountsIndexRequest $request)
    {
        $data = $request->validated();
        $result = $this->repo->all(
            $data['search'] ?? null,
            $data['rowsPerPage'] ?? 10,
            $data['page'] ?? 1,
        );

        return response()->json([
            'data' => DiscountTableResource::collection($result->items()),
            'meta' => [
                'total' => $result->total(),
                'per_page' => $result->perPage(),
                'current_page' => $result->currentPage(),
                'last_page' => $result->lastPage(),
                'from' => $result->firstItem(),
                'to' => $result->lastItem(),
            ],
            'links' => [
                'first' => $result->url(1),
                'last' => $result->url($result->lastPage()),
                'prev' => $result->previousPageUrl(),
                'next' => $result->nextPageUrl(),
            ],
        ]);
    }

    public function show($id)
    {
        return response()->json([
            'data' => new DiscountTableResource($this->repo->findForFrontend((int) $id)),
        ]);
    }

    public function update(UpdateDiscountRequest $request, $id)
    {
        $validated = $request->validated();
        $current = $this->repo->find((int) $id);
        $shopifyExecuted = $this->shopifyFirstSyncService->syncOrFail($validated, (string) $current->store_id);
        $updated = $this->shopifySync->update(
            $this->repo->find((int) $id),
            array_keys($validated),
            fn () => $this->repo->update((int) $id, $validated),
        );
        $outboundSyncId = $shopifyExecuted ? null : $this->outboundSyncDispatcher->dispatchFromValidated(
            validated: $validated,
            storeId: (string) $updated->store_id,
            entityType: 'discount',
            entityId: (string) $updated->id,
            action: 'update',
        );

        return response()->json([
            'message' => 'Discount updated successfully',
            'data' => new DiscountTableResource($updated),
            'meta' => [
                'outbound_sync_id' => $outboundSyncId,
                'shopify_sync' => $this->shopifySync->report(),
            ],
        ]);
    }

    public function store()
    {
        $validated = request()->validate([
            'store_id' => ['required', 'uuid'],
            'discount_type' => ['nullable', 'string', 'max:255'],
            'method' => ['nullable', 'string', 'max:255'],
            'title' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'string', 'max:255'],
            'summary' => ['nullable', 'string'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after:starts_at'],
            'usage_limit' => ['nullable', 'integer', 'min:1'],
            // Needed to create the discount in Shopify (basic discount on the whole order).
            'code' => ['nullable', 'string', 'max:255'],
            'value' => ['nullable', 'numeric', 'min:0'],
            'value_type' => ['nullable', 'in:percentage,fixed_amount'],
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

        $shopifyExecuted = $this->shopifyFirstSyncService->syncOrFail($validated, (string) $validated['store_id']);
        $createInput = Arr::only($validated, ['code', 'value', 'value_type']);
        $attributes = Arr::except($validated, ['code', 'value', 'value_type']);
        $attributes['raw_payload'] = $createInput !== [] ? ['create_input' => $createInput] : null;
        $created = $this->shopifySync->create(fn () => $this->repo->create($attributes));
        $outboundSyncId = $shopifyExecuted ? null : $this->outboundSyncDispatcher->dispatchFromValidated(
            validated: $validated,
            storeId: (string) $created->store_id,
            entityType: 'discount',
            entityId: (string) $created->id,
            action: 'create',
        );

        return response()->json([
            'message' => 'Discount created successfully',
            'data' => new DiscountTableResource($created),
            'meta' => [
                'outbound_sync_id' => $outboundSyncId,
                'shopify_sync' => $this->shopifySync->report(),
            ],
        ], 201);
    }

    public function destroy(int $id)
    {
        $validated = request()->validate([
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

        $discount = $this->repo->find((int) $id);
        $storeId = (string) $discount->store_id;
        $entityId = (string) $discount->id;
        $shopifyExecuted = $this->shopifyFirstSyncService->syncOrFail($validated, $storeId);
        $this->shopifySync->delete($discount, fn () => $this->repo->delete((int) $id));

        $outboundSyncId = $shopifyExecuted ? null : $this->outboundSyncDispatcher->dispatchFromValidated(
            validated: $validated,
            storeId: $storeId,
            entityType: 'discount',
            entityId: $entityId,
            action: 'delete',
        );

        return response()->json([
            'message' => 'Discount deleted successfully',
            'meta' => [
                'outbound_sync_id' => $outboundSyncId,
                'shopify_sync' => $this->shopifySync->report(),
            ],
        ]);
    }
}
