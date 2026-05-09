<?php

namespace App\Modules\Catalog\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Catalog\Models\Collection;
use App\Modules\Catalog\Models\Option;
use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\ProductVariant;
use App\Modules\Catalog\Repositories\Interfaces\ProductsRepositoryInterface;
use App\Modules\Catalog\Requests\ProductIndexRequest;
use App\Modules\Catalog\Requests\UpdateProductRequest;
use App\Modules\Catalog\Resources\ProductDetailResource;
use App\Modules\Catalog\Resources\ProductTableResource;
use App\Modules\Shopify\OutboundSync\Services\LocalChangeOutboundSyncDispatcher;
use App\Modules\Shopify\OutboundSync\Services\ShopifyFirstSyncService;
use App\Modules\Shopify\Services\ShopifyClient;
use App\Modules\Stores\Models\Store;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class ProductController extends Controller
{

    protected $repo;

    public function __construct(
        ProductsRepositoryInterface $repo,
        private readonly LocalChangeOutboundSyncDispatcher $outboundSyncDispatcher,
        private readonly ShopifyFirstSyncService $shopifyFirstSyncService,
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
        $shopifyExecuted = $this->shopifyFirstSyncService->syncOrFail($validated, $storeId);

        $created = $this->repo->create($validated);
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
            'meta' => ['outbound_sync_id' => $outboundSyncId],
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
        $updated = $this->repo->update((int) $id, $validated);
        $outboundSyncId = $shopifyExecuted ? null : $this->outboundSyncDispatcher->dispatchFromValidated(
                validated: $validated,
                storeId: (string) $updated->store_id,
                entityType: 'product',
                entityId: (string) $updated->id,
                action: 'update',
            );
        $autoSyncIds = $this->dispatchAutomaticProductDeltaSync($before, $updated, $validated);

        return response()->json([
            'message' => 'Product updated successfully',
            'data' => new ProductDetailResource($updated),
            'meta' => [
                'outbound_sync_id' => $outboundSyncId,
                'auto_outbound_sync_ids' => $autoSyncIds,
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
        $this->repo->delete((int) $product->id);

        $outboundSyncId = $shopifyExecuted ? null : $this->outboundSyncDispatcher->dispatchFromValidated(
                validated: $validated,
                storeId: $storeId,
                entityType: 'product',
                entityId: $entityId,
                action: 'delete',
            );

        return response()->json([
            'message' => 'Product deleted successfully',
            'meta' => ['outbound_sync_id' => $outboundSyncId],
        ]);
    }

    public function changeStatus($id)
    {
        $icon = $this->repo->toggleStatus($id);

        return response()->json([
            'success' => true,
            'message' => 'Status changed successfully',
            'data' => new ProductTableResource($icon)
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
            ],
        ]);
    }

    /**
     * @param array<string, mixed> $validated
     * @return array<int, int>
     */
    private function dispatchAutomaticProductDeltaSync(Product $before, Product $after, array $validated): array
    {
        $syncIds = [];
        $storeId = (string) $after->store_id;
        $productGid = $this->asShopifyGid($after->shopify_product_id, 'Product');

        if (!$productGid) {
            return $syncIds;
        }

        $productUpdatePayload = $this->buildProductUpdatePayload($after, $validated, $productGid);
        if ($productUpdatePayload !== null) {
            $syncId = $this->outboundSyncDispatcher->dispatchGraphql(
                storeId: $storeId,
                entityType: 'product',
                entityId: (string) $after->id,
                action: 'update',
                payload: $productUpdatePayload,
                priority: 5,
                maxAttempts: 5,
            );

            if ($syncId) {
                $syncIds[] = $syncId;
            }
        }

        if (array_key_exists('collection_ids', $validated)) {
            $beforeIds = $before->collections()->pluck('collections.id')->map(fn ($id) => (int) $id)->values()->all();
            $afterIds = $after->collections()->pluck('collections.id')->map(fn ($id) => (int) $id)->values()->all();

            $toAdd = array_values(array_diff($afterIds, $beforeIds));
            $toRemove = array_values(array_diff($beforeIds, $afterIds));

            foreach ($toAdd as $collectionId) {
                $collection = Collection::query()->find($collectionId);
                $collectionGid = $this->asShopifyGid($collection?->shopify_collection_id, 'Collection');
                if (!$collectionGid) {
                    continue;
                }

                $payload = [
                    'mutation' => <<<'GQL'
mutation CollectionAddProducts($id: ID!, $productIds: [ID!]!) {
  collectionAddProducts(id: $id, productIds: $productIds) {
    collection { id }
    userErrors { field message }
  }
}
GQL,
                    'variables' => [
                        'id' => $collectionGid,
                        'productIds' => [$productGid],
                    ],
                    'resource_path' => 'data.collectionAddProducts.collection.id',
                    'user_errors_path' => 'data.collectionAddProducts.userErrors',
                ];

                $syncId = $this->outboundSyncDispatcher->dispatchGraphql(
                    storeId: $storeId,
                    entityType: 'collection_product',
                    entityId: (string) $after->id,
                    action: 'update',
                    payload: $payload,
                    priority: 5,
                    maxAttempts: 5,
                );

                if ($syncId) {
                    $syncIds[] = $syncId;
                }
            }

            foreach ($toRemove as $collectionId) {
                $collection = Collection::query()->find($collectionId);
                $collectionGid = $this->asShopifyGid($collection?->shopify_collection_id, 'Collection');
                if (!$collectionGid) {
                    continue;
                }

                $payload = [
                    'mutation' => <<<'GQL'
mutation CollectionRemoveProducts($id: ID!, $productIds: [ID!]!) {
  collectionRemoveProducts(id: $id, productIds: $productIds) {
    job { id done }
    userErrors { field message }
  }
}
GQL,
                    'variables' => [
                        'id' => $collectionGid,
                        'productIds' => [$productGid],
                    ],
                    'resource_path' => 'data.collectionRemoveProducts.job.id',
                    'user_errors_path' => 'data.collectionRemoveProducts.userErrors',
                ];

                $syncId = $this->outboundSyncDispatcher->dispatchGraphql(
                    storeId: $storeId,
                    entityType: 'collection_product',
                    entityId: (string) $after->id,
                    action: 'update',
                    payload: $payload,
                    priority: 5,
                    maxAttempts: 5,
                );

                if ($syncId) {
                    $syncIds[] = $syncId;
                }
            }
        }

        if (array_key_exists('option_ids', $validated)) {
            $optionSyncIds = $this->dispatchProductOptionsDeltaSync($before, $after, $storeId, $productGid);
            $syncIds = array_merge($syncIds, $optionSyncIds);
        }

        return $syncIds;
    }

    /**
     * @param array<string, mixed> $validated
     * @return array<string, mixed>|null
     */
    private function buildProductUpdatePayload(Product $after, array $validated, string $productGid): ?array
    {
        $input = ['id' => $productGid];

        if (array_key_exists('title', $validated)) {
            $input['title'] = (string) $after->title;
        }
        if (array_key_exists('description', $validated)) {
            $input['descriptionHtml'] = (string) ($after->description ?? '');
        }
        if (array_key_exists('handle', $validated)) {
            $input['handle'] = (string) $after->handle;
        }
        if (array_key_exists('vendor_id', $validated)) {
            $input['vendor'] = (string) ($after->vendor?->name ?? '');
        }
        if (array_key_exists('product_type_id', $validated)) {
            $input['productType'] = (string) ($after->productType?->name ?? '');
        }
        if (array_key_exists('category_id', $validated)) {
            $input['category'] = $this->asShopifyGid($after->category?->shopify_category_id, 'TaxonomyCategory');
        }
        if (array_key_exists('tag_ids', $validated)) {
            $input['tags'] = $after->tags()->pluck('name')->filter()->values()->all();
        }
        if (array_key_exists('status', $validated)) {
            $status = strtoupper((string) $after->status);
            $input['status'] = in_array($status, ['ACTIVE', 'DRAFT', 'ARCHIVED'], true) ? $status : 'DRAFT';
        }
        if (array_key_exists('seo_title', $validated) || array_key_exists('seo_description', $validated)) {
            $input['seo'] = [
                'title' => $after->seo_title,
                'description' => $after->seo_description,
            ];
        }

        if (count($input) === 1) {
            return null;
        }

        return [
            'mutation' => <<<'GQL'
mutation ProductUpdate($input: ProductInput!) {
  productUpdate(input: $input) {
    product { id }
    userErrors { field message }
  }
}
GQL,
            'variables' => [
                'input' => $input,
            ],
            'resource_path' => 'data.productUpdate.product.id',
            'user_errors_path' => 'data.productUpdate.userErrors',
        ];
    }

    /**
     * @return array<int, int>
     */
    private function dispatchProductOptionsDeltaSync(Product $before, Product $after, string $storeId, string $productGid): array
    {
        $syncIds = [];
        $beforeOptions = $before->options()->pluck('name')->filter()->values()->all();
        $afterOptionIds = $after->options()->pluck('options.id')->map(fn ($id) => (int) $id)->values()->all();
        $afterOptionsByName = $after->options()->pluck('name')->filter()->values()->all();

        $addedNames = array_values(array_diff($afterOptionsByName, $beforeOptions));
        $removedNames = array_values(array_diff($beforeOptions, $afterOptionsByName));

        if ($addedNames !== []) {
            $addedOptions = Option::query()
                ->with('values:id,option_id,label,value')
                ->whereIn('id', $afterOptionIds)
                ->whereIn('name', $addedNames)
                ->get();

            foreach ($addedOptions as $addedOption) {
                $values = $addedOption->values
                    ->map(fn ($value) => ['name' => (string) ($value->label ?: $value->value)])
                    ->filter(fn ($value) => trim((string) $value['name']) !== '')
                    ->values()
                    ->all();

                if ($values === []) {
                    $values = [['name' => 'Default']];
                }

                $payload = [
                    'mutation' => <<<'GQL'
mutation ProductOptionsCreate($productId: ID!, $options: [OptionCreateInput!]!, $variantStrategy: ProductOptionCreateVariantStrategy) {
  productOptionsCreate(productId: $productId, options: $options, variantStrategy: $variantStrategy) {
    product { id }
    userErrors { field message code }
  }
}
GQL,
                    'variables' => [
                        'productId' => $productGid,
                        'options' => [[
                            'name' => (string) $addedOption->name,
                            'values' => $values,
                        ]],
                        'variantStrategy' => 'LEAVE_AS_IS',
                    ],
                    'resource_path' => 'data.productOptionsCreate.product.id',
                    'user_errors_path' => 'data.productOptionsCreate.userErrors',
                ];

                $syncId = $this->outboundSyncDispatcher->dispatchGraphql(
                    storeId: $storeId,
                    entityType: 'product_option',
                    entityId: (string) $after->id,
                    action: 'update',
                    payload: $payload,
                    priority: 5,
                    maxAttempts: 5,
                );

                if ($syncId) {
                    $syncIds[] = $syncId;
                }
            }
        }

        if ($removedNames !== []) {
            $store = Store::query()->find($storeId);

            if ($store) {
                $response = (new ShopifyClient($store))->query(
                    <<<'GQL'
query ProductOptions($id: ID!) {
  product(id: $id) {
    options {
      id
      name
    }
  }
}
GQL,
                    ['id' => $productGid]
                );

                $optionIdsToDelete = collect($response['data']['product']['options'] ?? [])
                    ->filter(fn ($option) => in_array((string) ($option['name'] ?? ''), $removedNames, true))
                    ->pluck('id')
                    ->filter(fn ($id) => is_string($id) && $id !== '')
                    ->values()
                    ->all();

                if ($optionIdsToDelete !== []) {
                    $payload = [
                        'mutation' => <<<'GQL'
mutation ProductOptionsDelete($productId: ID!, $options: [ID!]!, $strategy: ProductOptionDeleteStrategy) {
  productOptionsDelete(productId: $productId, options: $options, strategy: $strategy) {
    product { id }
    userErrors { field message code }
  }
}
GQL,
                        'variables' => [
                            'productId' => $productGid,
                            'options' => $optionIdsToDelete,
                            'strategy' => 'NON_DESTRUCTIVE',
                        ],
                        'resource_path' => 'data.productOptionsDelete.product.id',
                        'user_errors_path' => 'data.productOptionsDelete.userErrors',
                    ];

                    $syncId = $this->outboundSyncDispatcher->dispatchGraphql(
                        storeId: $storeId,
                        entityType: 'product_option',
                        entityId: (string) $after->id,
                        action: 'update',
                        payload: $payload,
                        priority: 5,
                        maxAttempts: 5,
                    );

                    if ($syncId) {
                        $syncIds[] = $syncId;
                    }
                }
            }
        }

        return $syncIds;
    }

    private function asShopifyGid(?string $rawId, string $type): ?string
    {
        if (!$rawId) {
            return null;
        }

        if (str_starts_with($rawId, 'gid://')) {
            return $rawId;
        }

        $normalized = trim($rawId);
        if ($normalized === '' || !ctype_digit($normalized)) {
            return null;
        }

        return "gid://shopify/{$type}/{$normalized}";
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
