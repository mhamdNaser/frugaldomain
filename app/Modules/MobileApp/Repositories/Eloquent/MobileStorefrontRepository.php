<?php

namespace App\Modules\MobileApp\Repositories\Eloquent;

use App\Modules\Catalog\Models\Collection;
use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\ProductVariant;
use App\Modules\CMS\Models\Blog;
use App\Modules\CMS\Models\Menu;
use App\Modules\CMS\Models\MenuItem;
use App\Modules\CMS\Models\Page;
use App\Modules\Orders\Models\DraftOrder;
use App\Modules\Orders\Models\DraftOrderItem;
use App\Modules\Stores\Models\Store;
use App\Modules\User\Models\Customer;
use App\Modules\MobileApp\Repositories\Interfaces\MobileStorefrontRepositoryInterface;
use Illuminate\Support\Facades\DB;

class MobileStorefrontRepository implements MobileStorefrontRepositoryInterface
{
    public function bootstrap(string $storeId, ?string $warehouseName = null): array
    {
        $store = Store::query()->with(['settings', 'branding'])->findOrFail($storeId);

        $featuredProducts = $this->applyWarehouseScope(
            Product::query()->where('store_id', $storeId),
            $warehouseName
        )
            ->where('status', 'active')
            ->orderByDesc('updated_at')
            ->limit(12)
            ->with(['variants', 'files'])
            ->get();

        $collections = Collection::query()
            ->where('store_id', $storeId)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderByDesc('id')
            ->limit(12)
            ->get();

        $pages = Page::query()
            ->where('store_id', $storeId)
            ->where('is_published', true)
            ->orderByDesc('updated_at')
            ->limit(10)
            ->get();

        return [
            'store' => $store,
            'featured_products' => $featuredProducts,
            'collections' => $collections,
            'pages' => $pages,
            'payment_methods' => $this->paymentMethods($storeId),
        ];
    }

    public function navigation(string $storeId, array $filters = []): array
    {
        $menuHandle = trim((string) ($filters['menu_handle'] ?? ''));

        $menuQuery = Menu::query()
            ->where('store_id', $storeId)
            ->where('items_count', '>', 0);

        if ($menuHandle !== '') {
            $menuQuery->where('handle', $menuHandle);
        }

        $menu = $menuQuery
            ->orderByRaw("CASE WHEN handle IN ('main-menu', 'main') THEN 0 ELSE 1 END")
            ->orderByDesc('updated_at')
            ->first();

        if (!$menu) {
            return [
                'menu' => null,
                'items' => [],
            ];
        }

        $items = MenuItem::query()
            ->where('store_id', $storeId)
            ->where('menu_id', $menu->id)
            ->orderBy('position')
            ->orderBy('id')
            ->get();

        $groupedByParent = $items->groupBy(fn ($item) => $item->parent_id ?: 0);

        return [
            'menu' => [
                'id' => $menu->id,
                'title' => $menu->title,
                'handle' => $menu->handle,
                'shopify_menu_id' => $menu->shopify_menu_id,
            ],
            'items' => $this->buildMenuTree($groupedByParent, 0, $storeId),
        ];
    }

    public function listProducts(string $storeId, array $filters = [], ?string $warehouseName = null)
    {
        $perPage = max(1, min(50, (int) ($filters['per_page'] ?? 20)));
        $query = $this->applyWarehouseScope(
            Product::query()->where('store_id', $storeId),
            $warehouseName
        )
            ->where('status', 'active')
            ->with(['variants', 'files']);

        if (!empty($filters['q'])) {
            $q = (string) $filters['q'];
            $query->where(function ($inner) use ($q) {
                $inner->where('title', 'like', "%{$q}%")
                    ->orWhere('handle', 'like', "%{$q}%")
                    ->orWhere('description', 'like', "%{$q}%");
            });
        }

        if (!empty($filters['collection_id'])) {
            $collectionId = (int) $filters['collection_id'];
            $query->whereHas('collections', fn ($q) => $q->where('collections.id', $collectionId));
        }

        return $query->orderByDesc('updated_at')->paginate($perPage);
    }

    public function findProduct(string $storeId, int $productId, ?string $warehouseName = null)
    {
        return $this->applyWarehouseScope(
            Product::query()->where('store_id', $storeId),
            $warehouseName
        )
            ->where('id', $productId)
            ->with([
                'variants.optionValues.option',
                'variants.files',
                'files',
                'collections',
                'tags',
                'vendor',
                'productType',
                'category',
            ])
            ->firstOrFail();
    }

    public function listCollections(string $storeId, array $filters = [], ?string $warehouseName = null)
    {
        $perPage = max(1, min(50, (int) ($filters['per_page'] ?? 20)));

        return Collection::query()
            ->where('store_id', $storeId)
            ->when(array_key_exists('is_active', $filters), fn ($q) => $q->where('is_active', (bool) $filters['is_active']))
            ->orderBy('sort_order')
            ->orderByDesc('id')
            ->paginate($perPage);
    }

    public function findCollection(string $storeId, int $collectionId, ?string $warehouseName = null)
    {
        $collection = Collection::query()
            ->where('store_id', $storeId)
            ->where('id', $collectionId)
            ->firstOrFail();

        $collection->load([
            'products' => function ($query) use ($warehouseName) {
                $this->applyWarehouseScope($query, $warehouseName)->with(['variants', 'files']);
            },
        ]);

        return $collection;
    }

    public function listPages(string $storeId, array $filters = [])
    {
        $perPage = max(1, min(50, (int) ($filters['per_page'] ?? 20)));

        return Page::query()
            ->where('store_id', $storeId)
            ->when(array_key_exists('is_published', $filters), fn ($q) => $q->where('is_published', (bool) $filters['is_published']))
            ->orderByDesc('updated_at')
            ->paginate($perPage);
    }

    public function findPage(string $storeId, int $pageId)
    {
        return Page::query()
            ->where('store_id', $storeId)
            ->where('id', $pageId)
            ->firstOrFail();
    }

    public function listBlogs(string $storeId, array $filters = [])
    {
        $perPage = max(1, min(50, (int) ($filters['per_page'] ?? 20)));

        return Blog::query()
            ->where('store_id', $storeId)
            ->when(array_key_exists('is_published', $filters), fn ($q) => $q->where('is_published', (bool) $filters['is_published']))
            ->orderByDesc('updated_at')
            ->paginate($perPage);
    }

    public function listArticlesByBlog(string $storeId, int $blogId, array $filters = [])
    {
        $perPage = max(1, min(50, (int) ($filters['per_page'] ?? 20)));

        $blog = Blog::query()->where('store_id', $storeId)->where('id', $blogId)->firstOrFail();

        return $blog->articles()
            ->when(array_key_exists('is_published', $filters), fn ($q) => $q->where('is_published', (bool) $filters['is_published']))
            ->orderByDesc('published_at')
            ->paginate($perPage);
    }

    public function search(string $storeId, string $q, int $limit = 20, ?string $warehouseName = null): array
    {
        $safeLimit = max(1, min(50, $limit));

        $products = $this->applyWarehouseScope(
            Product::query()->where('store_id', $storeId),
            $warehouseName
        )
            ->where('status', 'active')
            ->where(function ($inner) use ($q) {
                $inner->where('title', 'like', "%{$q}%")
                    ->orWhere('handle', 'like', "%{$q}%");
            })
            ->limit($safeLimit)
            ->get(['id', 'title', 'handle', 'featured_image', 'price_min', 'price_max', 'warehouse_location']);

        $collections = $this->applyWarehouseScope(
            Collection::query()->where('store_id', $storeId),
            $warehouseName
        )
            ->where('title', 'like', "%{$q}%")
            ->limit($safeLimit)
            ->get(['id', 'title', 'handle', 'image_url']);

        $pages = Page::query()
            ->where('store_id', $storeId)
            ->where('title', 'like', "%{$q}%")
            ->limit($safeLimit)
            ->get(['id', 'title', 'handle']);

        return [
            'products' => $products,
            'collections' => $collections,
            'pages' => $pages,
        ];
    }

    public function paymentMethods(string $storeId): array
    {
        $settings = DB::table('store_settings')->where('store_id', $storeId)->first();

        $enabled = [];
        if ($settings && (bool) ($settings->enable_cod ?? false)) {
            $enabled[] = ['key' => 'cod', 'label' => 'Cash on Delivery'];
        }
        if ($settings && (bool) ($settings->enable_stripe ?? false)) {
            $enabled[] = ['key' => 'stripe', 'label' => 'Credit/Debit Card (Stripe)'];
        }

        return [
            'enabled_locally' => $enabled,
            'supported_shopify' => [
                ['key' => 'shopify_payments', 'label' => 'Shopify Payments'],
                ['key' => 'shop_pay', 'label' => 'Shop Pay'],
                ['key' => 'paypal', 'label' => 'PayPal Express'],
                ['key' => 'manual', 'label' => 'Manual Payment Methods'],
                ['key' => 'third_party', 'label' => 'Third-party providers via payment apps'],
                ['key' => 'wallets', 'label' => 'Apple Pay / Google Pay (via supported gateways)'],
            ],
        ];
    }

    public function checkoutQuote(string $storeId, array $lines, ?string $warehouseName = null): array
    {
        $variantIds = collect($lines)->pluck('variant_id')->map(fn ($v) => (int) $v)->filter()->values();

        $variants = ProductVariant::query()
            ->where('store_id', $storeId)
            ->whereHas('product', function ($query) use ($warehouseName) {
                $this->applyWarehouseScope($query, $warehouseName);
            })
            ->whereIn('id', $variantIds)
            ->with('product')
            ->get()
            ->keyBy('id');

        $resultLines = [];
        $subtotal = 0.0;

        foreach ($lines as $line) {
            $variantId = (int) ($line['variant_id'] ?? 0);
            $qty = max(1, (int) ($line['quantity'] ?? 1));

            $variant = $variants->get($variantId);
            if (!$variant) {
                continue;
            }

            $unitPrice = (float) ($variant->price ?? 0);
            $lineTotal = $unitPrice * $qty;
            $subtotal += $lineTotal;

            $resultLines[] = [
                'variant_id' => $variant->id,
                'product_id' => $variant->product_id,
                'product_title' => $variant->product?->title,
                'variant_title' => $variant->title,
                'sku' => $variant->sku,
                'quantity' => $qty,
                'unit_price' => round($unitPrice, 2),
                'line_total' => round($lineTotal, 2),
            ];
        }

        $tax = 0.0;
        $shipping = 0.0;
        $discount = 0.0;

        return [
            'lines' => $resultLines,
            'totals' => [
                'subtotal' => round($subtotal, 2),
                'tax' => round($tax, 2),
                'shipping' => round($shipping, 2),
                'discount' => round($discount, 2),
                'grand_total' => round($subtotal + $tax + $shipping - $discount, 2),
            ],
        ];
    }

    public function createDraftOrder(string $storeId, array $payload, ?string $warehouseName = null): array
    {
        return DB::transaction(function () use ($storeId, $payload, $warehouseName) {
            $quote = $this->checkoutQuote($storeId, $payload['lines'] ?? [], $warehouseName);
            $totals = $quote['totals'];

            $customerId = isset($payload['customer_id']) ? (int) $payload['customer_id'] : null;
            $customer = null;
            if ($customerId) {
                $customer = Customer::query()
                    ->where('store_id', $storeId)
                    ->where('id', $customerId)
                    ->first();
            }

            $draftOrder = DraftOrder::query()->create([
                'store_id' => $storeId,
                'customer_id' => $customer?->id,
                'shopify_customer_id' => $customer?->shopify_customer_id,
                'name' => 'MOB-' . now()->format('YmdHis'),
                'status' => 'open',
                'subtotal' => $totals['subtotal'],
                'tax' => $totals['tax'],
                'total' => $totals['grand_total'],
                'currency' => (string) ($payload['currency'] ?? 'USD'),
                'raw_payload' => [
                    'source' => 'mobile_app',
                    'warehouse_name' => $warehouseName,
                    'notes' => $payload['notes'] ?? null,
                    'shipping_address' => $payload['shipping_address'] ?? null,
                    'billing_address' => $payload['billing_address'] ?? null,
                ],
            ]);

            foreach ($quote['lines'] as $line) {
                DraftOrderItem::query()->create([
                    'store_id' => $storeId,
                    'draft_order_id' => $draftOrder->id,
                    'variant_id' => $line['variant_id'],
                    'product_title' => $line['product_title'],
                    'variant_title' => $line['variant_title'],
                    'sku' => $line['sku'],
                    'quantity' => $line['quantity'],
                    'unit_price' => $line['unit_price'],
                    'total_price' => $line['line_total'],
                    'raw_payload' => $line,
                ]);
            }

            return [
                'draft_order_id' => $draftOrder->id,
                'name' => $draftOrder->name,
                'status' => $draftOrder->status,
                'totals' => $totals,
                'sync_note' => 'Draft order saved locally. To push to Shopify, bind this flow with outbound sync draft-order mutation.',
            ];
        });
    }

    private function applyWarehouseScope($query, ?string $warehouseName)
    {
        $name = trim((string) $warehouseName);
        if ($name === '' || strtolower($name) === 'all') {
            return $query;
        }

        return $query->whereRaw('LOWER(TRIM(warehouse_location)) = ?', [strtolower($name)]);
    }

    private function buildMenuTree($groupedByParent, int $parentId, string $storeId): array
    {
        $children = $groupedByParent->get($parentId, collect());

        return $children->map(function (MenuItem $item) use ($groupedByParent, $storeId) {
            $resolved = $this->resolveMenuTarget($item, $storeId);

            return [
                'id' => $item->id,
                'title' => (string) ($item->title ?? ''),
                'type' => $item->type,
                'position' => (int) $item->position,
                'resource_id' => $item->resource_id,
                'url' => $item->url,
                'action' => $resolved['action'],
                'target' => $resolved['target'],
                'children' => $this->buildMenuTree($groupedByParent, (int) $item->id, $storeId),
            ];
        })->values()->all();
    }

    private function resolveMenuTarget(MenuItem $item, string $storeId): array
    {
        $resourceId = (string) ($item->resource_id ?? '');
        $normalizedType = strtolower((string) ($item->type ?? ''));
        $url = (string) ($item->url ?? '');

        $numericFromResource = $this->extractNumericId($resourceId);
        $numericFromUrl = $this->extractNumericId($url);

        $collection = null;
        if ($resourceId !== '' || str_contains($normalizedType, 'collection')) {
            $collection = Collection::query()
                ->where('store_id', $storeId)
                ->where(function ($q) use ($resourceId, $numericFromResource, $numericFromUrl, $url) {
                    if ($resourceId !== '') {
                        $q->orWhere('shopify_collection_id', $resourceId);
                    }
                    if ($numericFromResource) {
                        $q->orWhere('shopify_collection_id', $numericFromResource);
                    }
                    if ($numericFromUrl) {
                        $q->orWhere('shopify_collection_id', $numericFromUrl);
                    }
                    if ($url !== '') {
                        $q->orWhere('handle', basename(trim($url, '/')));
                    }
                })
                ->select(['id', 'title', 'handle', 'shopify_collection_id'])
                ->first();
        }
        if ($collection) {
            return [
                'action' => 'open_collection',
                'target' => [
                    'collection_id' => $collection->id,
                    'title' => $collection->title,
                    'handle' => $collection->handle,
                ],
            ];
        }

        $page = null;
        if ($resourceId !== '' || str_contains($normalizedType, 'page')) {
            $page = Page::query()
                ->where('store_id', $storeId)
                ->where(function ($q) use ($resourceId, $numericFromResource, $numericFromUrl, $url) {
                    if ($resourceId !== '') {
                        $q->orWhere('shopify_page_id', $resourceId);
                    }
                    if ($numericFromResource) {
                        $q->orWhere('shopify_page_id', $numericFromResource);
                    }
                    if ($numericFromUrl) {
                        $q->orWhere('shopify_page_id', $numericFromUrl);
                    }
                    if ($url !== '') {
                        $q->orWhere('handle', basename(trim($url, '/')));
                    }
                })
                ->select(['id', 'title', 'handle', 'shopify_page_id'])
                ->first();
        }
        if ($page) {
            return [
                'action' => 'open_page',
                'target' => [
                    'page_id' => $page->id,
                    'title' => $page->title,
                    'handle' => $page->handle,
                ],
            ];
        }

        $product = null;
        if ($resourceId !== '' || str_contains($normalizedType, 'product')) {
            $product = Product::query()
                ->where('store_id', $storeId)
                ->where(function ($q) use ($resourceId, $numericFromResource, $numericFromUrl, $url) {
                    if ($resourceId !== '') {
                        $q->orWhere('shopify_product_id', $resourceId);
                    }
                    if ($numericFromResource) {
                        $q->orWhere('shopify_product_id', $numericFromResource);
                    }
                    if ($numericFromUrl) {
                        $q->orWhere('shopify_product_id', $numericFromUrl);
                    }
                    if ($url !== '') {
                        $q->orWhere('handle', basename(trim($url, '/')));
                    }
                })
                ->select(['id', 'title', 'handle', 'shopify_product_id'])
                ->first();
        }
        if ($product) {
            return [
                'action' => 'open_product',
                'target' => [
                    'product_id' => $product->id,
                    'title' => $product->title,
                    'handle' => $product->handle,
                ],
            ];
        }

        if ($url !== '') {
            return [
                'action' => 'open_url',
                'target' => [
                    'url' => $url,
                ],
            ];
        }

        return [
            'action' => 'none',
            'target' => null,
        ];
    }

    private function extractNumericId(?string $value): ?string
    {
        $input = trim((string) $value);
        if ($input === '') {
            return null;
        }

        if (preg_match('/(\d+)(?!.*\d)/', $input, $matches) === 1) {
            return $matches[1];
        }

        return null;
    }
}
