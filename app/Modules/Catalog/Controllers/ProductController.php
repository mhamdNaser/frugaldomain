<?php

namespace App\Modules\Catalog\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\ProductVariant;
use App\Modules\Catalog\Repositories\Interfaces\ProductsRepositoryInterface;
use App\Modules\Catalog\Requests\ProductIndexRequest;
use App\Modules\Catalog\Requests\UpdateProductRequest;
use App\Modules\Catalog\Resources\ProductDetailResource;
use App\Modules\Catalog\Resources\ProductTableResource;
use App\Modules\Shopify\AutoSync\ShopifyAutoSync;
use App\Modules\Shopify\OutboundSync\Services\LocalChangeOutboundSyncDispatcher;
use App\Modules\Shopify\OutboundSync\Services\ShopifyFirstSyncService;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class ProductController extends Controller
{

    protected $repo;

    public function __construct(
        ProductsRepositoryInterface $repo,
        private readonly LocalChangeOutboundSyncDispatcher $outboundSyncDispatcher,
        private readonly ShopifyFirstSyncService $shopifyFirstSyncService,
        private readonly ShopifyAutoSync $shopifySync,
    )
    {
        $this->repo = $repo;
    }
    /**
     * Display a listing of the resource.
     */
    public function index(ProductIndexRequest $request)
    {
        $data = $request->validated();
        $search = $data['search'] ?? null;
        $rowsPerPage = $data['rowsPerPage'] ?? 10;
        $page = $data['page'] ?? 1;

        $result = $this->repo->all($search, $rowsPerPage, $page);

        return response()->json([
            'data' => ProductTableResource::collection($result->items()),
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

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255'],
            'handle' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'status' => ['nullable', 'string', 'max:50'],
            'warehouse_location' => ['nullable', 'string', 'max:255'],
            'store_id' => ['nullable', 'uuid'],
            'vendor_id' => ['nullable', 'integer', 'exists:vendors,id'],
            'product_type_id' => ['nullable', 'integer', 'exists:product_types,id'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'tags' => ['nullable', 'array'],
            'tag_ids' => ['nullable', 'array'],
            'tag_ids.*' => ['integer', 'exists:tags,id'],
            'collection_ids' => ['nullable', 'array'],
            'collection_ids.*' => ['integer', 'exists:collections,id'],
            'option_ids' => ['nullable', 'array'],
            'option_ids.*' => ['integer', 'exists:options,id'],
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

        $storeId = $this->resolveStoreIdForCreate($validated);
        $validated['store_id'] = $storeId;
        $shopifyExecuted = $this->shopifyFirstSyncService->syncOrFail($validated, $storeId);

        $created = $this->shopifySync->create(fn () => $this->repo->create($validated));
        $outboundSyncId = $shopifyExecuted ? null : $this->outboundSyncDispatcher->dispatchFromValidated(
                validated: $validated,
                storeId: (string) $created->store_id,
                entityType: 'product',
                entityId: (string) $created->id,
                action: 'create',
            );

        return response()->json([
            'message' => 'Product created successfully',
            'data' => new ProductDetailResource($created),
            'meta' => [
                'outbound_sync_id' => $outboundSyncId,
                'shopify_sync' => $this->shopifySync->report(),
            ],
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        return response()->json([
            'data' => new ProductDetailResource($this->repo->findForFrontend((int) $id)),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateProductRequest $request, $id)
    {
        $validated = $request->validated();
        $before = $this->repo->findForFrontend((int) $id);
        $shopifyExecuted = $this->shopifyFirstSyncService->syncOrFail($validated, (string) $before->store_id);
        $updated = $this->shopifySync->update(
            $before,
            array_keys($validated),
            fn () => $this->repo->update((int) $id, $validated),
        );
        $outboundSyncId = $shopifyExecuted ? null : $this->outboundSyncDispatcher->dispatchFromValidated(
                validated: $validated,
                storeId: (string) $updated->store_id,
                entityType: 'product',
                entityId: (string) $updated->id,
                action: 'update',
            );

        return response()->json([
            'message' => 'Product updated successfully',
            'data' => new ProductDetailResource($updated),
            'meta' => [
                'outbound_sync_id' => $outboundSyncId,
                'shopify_sync' => $this->shopifySync->report(),
            ],
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Product $product)
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

        $storeId = (string) $product->store_id;
        $entityId = (string) $product->id;
        $shopifyExecuted = $this->shopifyFirstSyncService->syncOrFail($validated, $storeId);
        $this->shopifySync->delete($product, fn () => $this->repo->delete((int) $product->id));

        $outboundSyncId = $shopifyExecuted ? null : $this->outboundSyncDispatcher->dispatchFromValidated(
                validated: $validated,
                storeId: $storeId,
                entityType: 'product',
                entityId: $entityId,
                action: 'delete',
            );

        return response()->json([
            'message' => 'Product deleted successfully',
            'meta' => [
                'outbound_sync_id' => $outboundSyncId,
                'shopify_sync' => $this->shopifySync->report(),
            ],
        ]);
    }

    public function changeStatus($id)
    {
        $product = $this->shopifySync->update(
            $this->repo->find((int) $id),
            ['status'],
            fn () => $this->repo->toggleStatus($id),
        );

        return response()->json([
            'success' => true,
            'message' => 'Status changed successfully',
            'data' => new ProductTableResource($product),
            'meta' => ['shopify_sync' => $this->shopifySync->report()],
        ]);
    }

    public function updateVariantsPrice(Request $request, $id)
    {
        $validated = $request->validate([
            'price' => ['required', 'numeric', 'min:0'],
            'compare_at_price' => ['nullable', 'numeric', 'min:0'],
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

        $product = $this->repo->find((int) $id);
        $shopifyExecuted = $this->shopifyFirstSyncService->syncOrFail($validated, (string) $product->store_id);
        $price = (float) $validated['price'];
        $compareAtPrice = array_key_exists('compare_at_price', $validated)
            ? ($validated['compare_at_price'] !== null ? (float) $validated['compare_at_price'] : null)
            : null;

        $updatedVariants = DB::transaction(function () use ($product, $price, $compareAtPrice, $validated) {
            $variants = ProductVariant::query()
                ->where('product_id', $product->id)
                ->where('store_id', $product->store_id)
                ->get();

            foreach ($variants as $variant) {
                $variant->price = $price;
                if (array_key_exists('compare_at_price', $validated)) {
                    $variant->compare_at_price = $compareAtPrice;
                }
                $variant->save();
            }

            $product->price_min = $price;
            $product->price_max = $price;
            $product->save();

            $this->shopifySync->run((string) $product->store_id, function ($ctx) use ($variants, $validated) {
                $changed = array_key_exists('compare_at_price', $validated) ? ['price', 'compare_at_price'] : ['price'];
                $syncer = $ctx->syncer(ProductVariant::class);

                foreach ($variants as $variant) {
                    $syncer->update($variant, [], $changed, $ctx);
                }
            });

            return $variants;
        });

        $outboundSyncIds = [];
        foreach ($updatedVariants as $variant) {
            $syncId = $shopifyExecuted ? null : $this->outboundSyncDispatcher->dispatchFromValidated(
                validated: $validated,
                storeId: (string) $product->store_id,
                entityType: 'product_variant',
                entityId: (string) $variant->id,
                action: 'update',
            );

            if ($syncId) {
                $outboundSyncIds[] = $syncId;
            }
        }

        return response()->json([
            'message' => 'Product price applied to all variants successfully',
            'data' => [
                'product_id' => $product->id,
                'applied_price' => $price,
                'applied_compare_at_price' => array_key_exists('compare_at_price', $validated) ? $compareAtPrice : null,
                'updated_variants_count' => count($updatedVariants),
            ],
            'meta' => [
                'outbound_sync_ids' => $outboundSyncIds,
                'shopify_sync' => $this->shopifySync->report(),
            ],
        ]);
    }

    /**
     * @param array<string, mixed> $validated
     */
    private function resolveStoreIdForCreate(array $validated): string
    {
        $storeId = (string) ($validated['store_id'] ?? '');

        if ($storeId !== '') {
            return $storeId;
        }

        $authStoreId = (string) (auth()->user()?->store?->id ?? '');
        abort_if($authStoreId === '', 422, 'store_id is required.');

        return $authStoreId;
    }
}
