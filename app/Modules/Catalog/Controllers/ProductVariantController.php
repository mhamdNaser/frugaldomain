<?php

namespace App\Modules\Catalog\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\ProductVariant;
use App\Modules\Catalog\Requests\StoreProductVariantRequest;
use App\Modules\Catalog\Requests\UpdateProductVariantRequest;
use App\Modules\Catalog\Resources\ProductVariantResource;
use App\Modules\Shopify\OutboundSync\Services\LocalChangeOutboundSyncDispatcher;
use App\Modules\Shopify\OutboundSync\Services\ShopifyFirstSyncService;
use Illuminate\Support\Arr;
use Illuminate\Http\Request;

class ProductVariantController extends Controller
{
    public function __construct(
        protected LocalChangeOutboundSyncDispatcher $outboundSyncDispatcher,
        protected ShopifyFirstSyncService $shopifyFirstSyncService,
    ) {}

    public function show(int $id)
    {
        return response()->json([
            'data' => new ProductVariantResource($this->findForFrontend($id)),
        ]);
    }

    public function index(Request $request)
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'rowsPerPage' => ['nullable', 'integer', 'min:1', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);

        $user = $request->user();
        $storeId = (string) ($user?->store?->id ?? '');
        abort_if($storeId === '', 404, 'No store is linked to the authenticated user.');

        $search = $validated['search'] ?? null;
        $rowsPerPage = (int) ($validated['rowsPerPage'] ?? 20);
        $page = (int) ($validated['page'] ?? 1);

        $result = ProductVariant::query()
            ->with(['product:id,title,handle,shopify_product_id', 'files'])
            ->where('store_id', $storeId)
            ->when($search, function ($query, string $search) {
                $query->where(function ($query) use ($search) {
                    $query->where('title', 'like', "%{$search}%")
                        ->orWhere('sku', 'like', "%{$search}%")
                        ->orWhere('barcode', 'like', "%{$search}%")
                        ->orWhereHas('product', fn ($productQuery) => $productQuery->where('title', 'like', "%{$search}%"));
                });
            })
            ->orderByDesc('id')
            ->paginate($rowsPerPage, ['*'], 'page', $page);

        return response()->json([
            'data' => ProductVariantResource::collection($result->items()),
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

    public function store(StoreProductVariantRequest $request)
    {
        $validated = $request->validated();
        $this->authorizeStoreAccess((string) $validated['store_id']);
        $product = $this->findProductForStore((int) $validated['product_id'], (string) $validated['store_id']);
        $shopifyExecuted = $this->shopifyFirstSyncService->syncOrFail($validated, (string) $product->store_id);

        $optionValueIds = Arr::pull($validated, 'option_value_ids', []);
        $variant = ProductVariant::query()->create($validated);
        $this->syncOptionValues($variant, $optionValueIds);
        $variant = $this->findForFrontend((int) $variant->id);

        $outboundSyncId = $shopifyExecuted ? null : $this->outboundSyncDispatcher->dispatchFromValidated(
                validated: $request->validated(),
                storeId: (string) $product->store_id,
                entityType: 'product_variant',
                entityId: (string) $variant->id,
                action: 'create',
            );

        return response()->json([
            'message' => 'Variant created successfully',
            'data' => new ProductVariantResource($variant),
            'meta' => ['outbound_sync_id' => $outboundSyncId],
        ], 201);
    }

    public function update(UpdateProductVariantRequest $request, int $id)
    {
        $validated = $request->validated();
        $variant = $this->findVariant($id);
        $this->authorizeStoreAccess((string) $variant->store_id);
        $shopifyExecuted = $this->shopifyFirstSyncService->syncOrFail($validated, (string) $variant->store_id);

        $optionValueIdsProvided = Arr::exists($validated, 'option_value_ids');
        $optionValueIds = Arr::pull($validated, 'option_value_ids', []);

        $variant->fill($validated);
        $variant->save();

        if ($optionValueIdsProvided) {
            $this->syncOptionValues($variant, $optionValueIds);
        }

        $variant = $this->findForFrontend((int) $variant->id);

        $outboundSyncId = $shopifyExecuted ? null : $this->outboundSyncDispatcher->dispatchFromValidated(
                validated: $request->validated(),
                storeId: (string) $variant->store_id,
                entityType: 'product_variant',
                entityId: (string) $variant->id,
                action: 'update',
            );

        return response()->json([
            'message' => 'Variant updated successfully',
            'data' => new ProductVariantResource($variant),
            'meta' => ['outbound_sync_id' => $outboundSyncId],
        ]);
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

        $variant = $this->findVariant($id);
        $this->authorizeStoreAccess((string) $variant->store_id);
        $storeId = (string) $variant->store_id;
        $entityId = (string) $variant->id;
        $shopifyExecuted = $this->shopifyFirstSyncService->syncOrFail($validated, $storeId);
        $variant->delete();

        $outboundSyncId = $shopifyExecuted ? null : $this->outboundSyncDispatcher->dispatchFromValidated(
                validated: $validated,
                storeId: $storeId,
                entityType: 'product_variant',
                entityId: $entityId,
                action: 'delete',
            );

        return response()->json([
            'message' => 'Variant deleted successfully',
            'meta' => ['outbound_sync_id' => $outboundSyncId],
        ]);
    }

    private function findVariant(int $id): ProductVariant
    {
        return ProductVariant::query()->findOrFail($id);
    }

    private function findProductForStore(int $productId, string $storeId): Product
    {
        return Product::query()
            ->where('id', $productId)
            ->where('store_id', $storeId)
            ->firstOrFail();
    }

    private function findForFrontend(int $id): ProductVariant
    {
        return ProductVariant::query()
            ->with([
                'files',
                'optionValues.option',
                'metafields.metaobjects',
                'priceLists',
                'priceListItems.priceList',
                'inventories.location',
                'inventoryLevels.location',
                'inventoryMovements.location',
            ])
            ->findOrFail($id);
    }

    private function syncOptionValues(ProductVariant $variant, array $optionValueIds): void
    {
        $ids = collect($optionValueIds)
            ->filter(fn ($id) => $id !== null && $id !== '')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->toArray();

        $variant->optionValues()->sync($ids);
    }

    private function authorizeStoreAccess(string $storeId): void
    {
        $user = auth()->user();

        if (
            $user
            && method_exists($user, 'hasRole')
            && $user->hasRole('partner')
            && !$user->hasRole('admin')
        ) {
            $linkedStoreId = (string) ($user->store?->id ?? '');
            abort_if(!$linkedStoreId, 404, 'No store is linked to the authenticated user.');
            abort_if($linkedStoreId !== $storeId, 403, 'You are not allowed to access this store.');
        }
    }
}
