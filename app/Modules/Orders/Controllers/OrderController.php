<?php

namespace App\Modules\Orders\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Catalog\Models\ProductVariant;
use App\Modules\Orders\Models\Order;
use App\Modules\Orders\Requests\CreateAdminOrderRequest;
use App\Modules\Orders\Repositories\Interfaces\OrdersRepositoryInterface;
use App\Modules\Orders\Requests\OrdersIndexRequest;
use App\Modules\Orders\Resources\OrderDetailResource;
use App\Modules\Orders\Requests\UpdateOrderRequest;
use App\Modules\Orders\Resources\OrderTableResource;
use App\Modules\Orders\Services\AdminOrderShopifySyncService;
use App\Modules\Shopify\OutboundSync\Services\LocalChangeOutboundSyncDispatcher;
use App\Modules\Shopify\OutboundSync\Services\ShopifyFirstSyncService;
use App\Modules\Stores\Models\Store;
use App\Modules\Tax\Models\TaxLine;
use App\Modules\User\Models\Customer;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Throwable;

class OrderController extends Controller
{
    public function __construct(
        protected OrdersRepositoryInterface $repo,
        protected LocalChangeOutboundSyncDispatcher $outboundSyncDispatcher,
        protected ShopifyFirstSyncService $shopifyFirstSyncService,
        protected AdminOrderShopifySyncService $adminOrderShopifySyncService,
    ) {}

    public function index(OrdersIndexRequest $request)
    {
        $data = $request->validated();
        $result = $this->repo->all(
            $data['search'] ?? null,
            $data['rowsPerPage'] ?? 10,
            $data['page'] ?? 1,
            $data['customer_id'] ?? null,
        );

        return response()->json([
            'data' => OrderTableResource::collection($result->items()),
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
            'data' => new OrderDetailResource($this->repo->findForFrontend((int) $id)),
        ]);
    }

    public function update(UpdateOrderRequest $request, $id)
    {
        $validated = $request->validated();
        $current = $this->repo->find((int) $id);
        $shopifyExecuted = $this->shopifyFirstSyncService->syncOrFail($validated, (string) $current->store_id);
        $updated = $this->repo->update((int) $id, $validated);
        $outboundSyncId = $shopifyExecuted ? null : $this->outboundSyncDispatcher->dispatchFromValidated(
            validated: $validated,
            storeId: (string) $updated->store_id,
            entityType: 'order',
            entityId: (string) $updated->id,
            action: 'update',
        );

        return response()->json([
            'message' => 'Order updated successfully',
            'data' => new OrderTableResource($updated),
            'meta' => [
                'outbound_sync_id' => $outboundSyncId,
            ],
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'store_id' => ['required', 'uuid'],
            'order_number' => ['required', 'string', 'max:255'],
            'status' => ['nullable', 'string', 'max:100'],
            'payment_status' => ['nullable', 'string', 'max:100'],
            'fulfillment_status' => ['nullable', 'string', 'max:100'],
            'email' => ['nullable', 'email', 'max:255'],
            'total' => ['nullable', 'numeric'],
            'currency' => ['nullable', 'string', 'max:20'],
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
        $created = $this->repo->create($validated);
        $outboundSyncId = $shopifyExecuted ? null : $this->outboundSyncDispatcher->dispatchFromValidated(
            validated: $validated,
            storeId: (string) $created->store_id,
            entityType: 'order',
            entityId: (string) $created->id,
            action: 'create',
        );

        return response()->json([
            'message' => 'Order created successfully',
            'data' => new OrderDetailResource($created),
            'meta' => ['outbound_sync_id' => $outboundSyncId],
        ], 201);
    }

    public function createFromAdmin(CreateAdminOrderRequest $request)
    {
        $validated = $request->validated();
        $storeId = $this->resolveStoreId($request);
        $store = Store::query()->findOrFail($storeId);

        $variants = $this->resolveOrderVariants($storeId, $validated['items']);
        $customer = $this->resolveOrderCustomer($storeId, $validated);
        $currency = strtoupper($validated['currency'] ?? $customer?->currency ?? 'USD');
        $shipping = (float) ($validated['shipping'] ?? 0);
        $tax = !empty($validated['tax_lines'])
            ? collect($validated['tax_lines'])->sum(fn (array $line) => (float) ($line['price'] ?? 0))
            : (float) ($validated['tax'] ?? 0);
        $discount = (float) ($validated['discount'] ?? 0);

        $subtotal = collect($validated['items'])->sum(function (array $item) use ($variants) {
            $variant = $variants->get((int) $item['variant_id']);
            $unitPrice = array_key_exists('unit_price', $item) && $item['unit_price'] !== null
                ? (float) $item['unit_price']
                : (float) $variant->price;

            return $unitPrice * (int) $item['quantity'];
        });
        $total = max(0, $subtotal + $shipping + $tax - $discount);

        $order = DB::transaction(function () use ($storeId, $validated, $variants, $customer, $currency, $shipping, $tax, $discount, $subtotal, $total) {
            $order = Order::query()->create([
                'store_id' => $storeId,
                'customer_id' => $customer?->id,
                'shopify_customer_id' => $customer?->shopify_customer_id,
                'email' => $customer?->email ?? data_get($validated, 'customer.email'),
                'order_number' => $this->nextLocalOrderNumber(),
                'status' => $validated['status'] ?? 'open',
                'payment_status' => $validated['payment_status'] ?? 'pending',
                'fulfillment_status' => $validated['fulfillment_status'] ?? 'unfulfilled',
                'subtotal' => $subtotal,
                'tax' => $tax,
                'shipping' => $shipping,
                'discount' => $discount,
                'total' => $total,
                'currency' => $currency,
                'placed_at' => now(),
                'raw_payload' => [
                    'note' => $validated['note'] ?? null,
                    'tags' => $validated['tags'] ?? ['local-admin'],
                    'source' => 'local_admin',
                ],
            ]);

            foreach ($validated['items'] as $item) {
                $variant = $variants->get((int) $item['variant_id']);
                $unitPrice = array_key_exists('unit_price', $item) && $item['unit_price'] !== null
                    ? (float) $item['unit_price']
                    : (float) $variant->price;
                $quantity = (int) $item['quantity'];

                $order->items()->create([
                    'store_id' => $storeId,
                    'variant_id' => $variant->id,
                    'shopify_product_id' => $variant->product?->shopify_product_id,
                    'shopify_variant_id' => $variant->shopify_variant_id,
                    'product_title' => $variant->product?->title ?? $variant->title,
                    'variant_title' => $variant->title,
                    'sku' => $variant->sku,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'total_price' => $unitPrice * $quantity,
                    'raw_payload' => [
                        'source' => 'local_admin',
                    ],
                ]);
            }

            $taxLines = $validated['tax_lines'] ?? [];
            if ($taxLines === [] && $tax > 0) {
                $taxLines[] = [
                    'title' => 'Manual tax',
                    'rate' => $subtotal > 0 ? round($tax / max(0.01, $subtotal), 6) : 0,
                    'rate_percentage' => $subtotal > 0 ? round(($tax / max(0.01, $subtotal)) * 100, 6) : 0,
                    'price' => $tax,
                    'channel_liable' => null,
                ];
            }

            foreach ($taxLines as $index => $taxLine) {
                TaxLine::query()->create([
                    'store_id' => $storeId,
                    'order_id' => $order->id,
                    'source_key' => hash('sha256', 'local_admin|' . $order->id . '|' . $index . '|' . ($taxLine['title'] ?? '') . '|' . ($taxLine['price'] ?? 0)),
                    'title' => $taxLine['title'] ?? 'Manual tax',
                    'rate' => (float) ($taxLine['rate'] ?? (($taxLine['rate_percentage'] ?? 0) / 100)),
                    'rate_percentage' => (float) ($taxLine['rate_percentage'] ?? (($taxLine['rate'] ?? 0) * 100)),
                    'price' => (float) ($taxLine['price'] ?? 0),
                    'currency' => $currency,
                    'channel_liable' => $taxLine['channel_liable'] ?? null,
                    'source' => 'local_admin',
                    'is_shipping' => false,
                    'raw_payload' => $taxLine,
                ]);
            }

            return $order->load(['customer', 'taxLines', 'items.variant.product']);
        });

        $syncResponse = null;
        if (($validated['sync_to_shopify'] ?? true) !== false) {
            try {
                $syncResponse = $this->adminOrderShopifySyncService->sync(
                    store: $store,
                    order: $order,
                    sendReceipt: (bool) ($validated['send_receipt'] ?? false),
                );
            } catch (Throwable $exception) {
                DB::transaction(function () use ($order) {
                    $order->items()->delete();
                    $order->taxLines()->delete();
                    $order->delete();
                });

                return response()->json([
                    'message' => 'Order sync failed on Shopify. No local order was saved.',
                    'error' => $exception->getMessage(),
                    'meta' => [
                        'synced_to_shopify' => false,
                    ],
                ], 422);
            }
        }

        return response()->json([
            'message' => 'Order created successfully',
            'data' => new OrderDetailResource($this->repo->findForFrontend((int) $order->id)),
            'meta' => [
                'synced_to_shopify' => $syncResponse !== null,
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

        $order = $this->repo->find((int) $id);
        $storeId = (string) $order->store_id;
        $entityId = (string) $order->id;
        $shopifyExecuted = $this->shopifyFirstSyncService->syncOrFail($validated, $storeId);
        $this->repo->delete((int) $id);

        $outboundSyncId = $shopifyExecuted ? null : $this->outboundSyncDispatcher->dispatchFromValidated(
            validated: $validated,
            storeId: $storeId,
            entityType: 'order',
            entityId: $entityId,
            action: 'delete',
        );

        return response()->json([
            'message' => 'Order deleted successfully',
            'meta' => ['outbound_sync_id' => $outboundSyncId],
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

    private function resolveOrderVariants(string $storeId, array $items)
    {
        $variantIds = collect($items)
            ->pluck('variant_id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        $variants = ProductVariant::query()
            ->with('product:id,store_id,shopify_product_id,title')
            ->where('store_id', $storeId)
            ->whereIn('id', $variantIds)
            ->get()
            ->keyBy('id');

        abort_if($variants->count() !== $variantIds->count(), 422, 'One or more variants do not belong to this store.');
        abort_if($variants->contains(fn ($variant) => blank($variant->shopify_variant_id)), 422, 'Every selected variant must have a Shopify variant ID.');

        return $variants;
    }

    private function resolveOrderCustomer(string $storeId, array $validated): ?Customer
    {
        if (!empty($validated['customer_id'])) {
            return Customer::query()
                ->where('store_id', $storeId)
                ->findOrFail((int) $validated['customer_id']);
        }

        $customerData = $validated['customer'] ?? null;
        if (!is_array($customerData)) {
            return null;
        }

        $email = $customerData['email'] ?? null;
        $phone = $customerData['phone'] ?? null;
        abort_if(!$email && !$phone, 422, 'A new customer requires email or phone.');

        $query = Customer::query()->where('store_id', $storeId);
        if ($email) {
            $query->where('email', $email);
        } else {
            $query->where('phone', $phone);
        }

        $customer = $query->first() ?: new Customer(['store_id' => $storeId]);
        $customer->fill([
            'first_name' => $customerData['first_name'] ?? $customer->first_name,
            'last_name' => $customerData['last_name'] ?? $customer->last_name,
            'display_name' => trim(($customerData['first_name'] ?? '') . ' ' . ($customerData['last_name'] ?? '')) ?: $customer->display_name,
            'email' => $email ?: $customer->email,
            'phone' => $phone ?: $customer->phone,
            'note' => $customerData['note'] ?? $customer->note,
            'status' => $customer->status ?: 'active',
        ]);
        $customer->save();

        return $customer;
    }

    private function nextLocalOrderNumber(): string
    {
        return 'LOCAL-' . now()->format('YmdHis') . '-' . random_int(100, 999);
    }
}
