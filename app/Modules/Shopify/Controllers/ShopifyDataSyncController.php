<?php

namespace App\Modules\Shopify\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Shopify\Jobs\Pagination\SyncProductsPaginationOrchestratorJob;
use App\Modules\Shopify\Jobs\SyncContentJob;
use App\Modules\Shopify\Jobs\SyncCustomersJob;
use App\Modules\Shopify\Jobs\SyncDiscountsJob;
use App\Modules\Shopify\Jobs\SyncDraftOrdersJob;
use App\Modules\Shopify\Jobs\SyncCustomerMarketingConsentJob;
use App\Modules\Shopify\Jobs\SyncFulfillmentsJob;
use App\Modules\Shopify\Jobs\SyncGlobalFilesJob;
use App\Modules\Shopify\Jobs\SyncInventoryStatesJob;
use App\Modules\Shopify\Jobs\SyncMarketsPriceListsJob;
use App\Modules\Shopify\Jobs\SyncMetaobjectDefinitionsJob;
use App\Modules\Shopify\Jobs\SyncOrderDutiesJob;
use App\Modules\Shopify\Jobs\SyncOrderRiskAndChannelsJob;
use App\Modules\Shopify\Jobs\SyncProductAdvancedMediaJob;
use App\Modules\Shopify\Jobs\SyncReturnsExchangesReverseJob;
use App\Modules\Shopify\Jobs\SyncShippingProfilesJob;
use App\Modules\Shopify\Jobs\SyncShopifyStoreDetailsJob;
use App\Modules\Shopify\Jobs\SyncSellingPlansJob;
use App\Modules\Shopify\Jobs\SyncStoreInstallsJob;
use App\Modules\Shopify\Jobs\SyncOrderFinancialsJob;
use App\Modules\Shopify\Jobs\SyncOrdersJob;
use App\Modules\Shopify\Jobs\SyncWebhookLogsJob;
use App\Modules\Shopify\Jobs\SyncWebhookSubscriptionsJob;
use App\Modules\Shopify\Services\Sync\SyncRunTracker;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Bus;

class ShopifyDataSyncController extends Controller
{
    public function __construct(
        private readonly SyncRunTracker $tracker,
    ) {}

    public function files(Request $request): JsonResponse
    {
        return $this->dispatchTracked($request, 'files', SyncGlobalFilesJob::class, 'Shopify global files sync started.');
    }

    public function customers(Request $request): JsonResponse
    {
        return $this->dispatchTracked($request, 'customers', SyncCustomersJob::class, 'Shopify customers sync started.');
    }

    public function orders(Request $request): JsonResponse
    {
        return $this->dispatchTracked($request, 'orders', SyncOrdersJob::class, 'Shopify orders sync started.');
    }

    public function draftOrders(Request $request): JsonResponse
    {
        return $this->dispatchTracked($request, 'draft-orders', SyncDraftOrdersJob::class, 'Shopify draft orders sync started.');
    }

    public function fulfillments(Request $request): JsonResponse
    {
        return $this->dispatchTracked($request, 'fulfillments', SyncFulfillmentsJob::class, 'Shopify fulfillments sync started.');
    }

    public function financials(Request $request): JsonResponse
    {
        return $this->dispatchTracked($request, 'financials', SyncOrderFinancialsJob::class, 'Shopify order transactions and refunds sync started.');
    }

    public function discounts(Request $request): JsonResponse
    {
        return $this->dispatchTracked($request, 'discounts', SyncDiscountsJob::class, 'Shopify discounts sync started.');
    }

    public function content(Request $request): JsonResponse
    {
        return $this->dispatchTracked($request, 'content', SyncContentJob::class, 'Shopify content sync started.');
    }

    public function shopDetails(Request $request): JsonResponse
    {
        return $this->dispatchTracked($request, 'shop-details', SyncShopifyStoreDetailsJob::class, 'Shopify shop details sync started.');
    }

    public function storeInstalls(Request $request): JsonResponse
    {
        return $this->dispatchTracked($request, 'store-installs', SyncStoreInstallsJob::class, 'Shopify store install sync started.');
    }

    public function webhookSubscriptions(Request $request): JsonResponse
    {
        return $this->dispatchTracked($request, 'webhook-subscriptions', SyncWebhookSubscriptionsJob::class, 'Shopify webhook subscriptions sync started.');
    }

    public function webhookLogs(Request $request): JsonResponse
    {
        return $this->dispatchTracked($request, 'webhook-logs', SyncWebhookLogsJob::class, 'Shopify webhook logs sync started.');
    }

    public function shipping(Request $request): JsonResponse
    {
        return $this->dispatchTracked($request, 'shipping', SyncShippingProfilesJob::class, 'Shopify shipping profiles sync started.');
    }

    public function returnsExchangesReverse(Request $request): JsonResponse
    {
        return $this->dispatchTracked($request, 'returns-exchanges-reverse', SyncReturnsExchangesReverseJob::class, 'Shopify returns/exchanges/reverse-fulfillment sync started.');
    }

    public function orderRiskChannel(Request $request): JsonResponse
    {
        return $this->dispatchTracked($request, 'order-risk-channel', SyncOrderRiskAndChannelsJob::class, 'Shopify order risk/channel sync started.');
    }

    public function orderDuties(Request $request): JsonResponse
    {
        return $this->dispatchTracked($request, 'order-duties', SyncOrderDutiesJob::class, 'Shopify order duties sync started.');
    }

    public function inventoryStates(Request $request): JsonResponse
    {
        return $this->dispatchTracked($request, 'inventory-states', SyncInventoryStatesJob::class, 'Shopify inventory states sync started.');
    }

    public function customerMarketingConsent(Request $request): JsonResponse
    {
        return $this->dispatchTracked($request, 'customer-marketing-consent', SyncCustomerMarketingConsentJob::class, 'Shopify customer marketing consent sync started.');
    }

    public function productAdvancedMedia(Request $request): JsonResponse
    {
        return $this->dispatchTracked($request, 'product-advanced-media', SyncProductAdvancedMediaJob::class, 'Shopify product advanced media sync started.');
    }

    public function marketsPriceLists(Request $request): JsonResponse
    {
        return $this->dispatchTracked($request, 'markets-price-lists', SyncMarketsPriceListsJob::class, 'Shopify markets and price lists sync started.');
    }

    public function metaobjectDefinitions(Request $request): JsonResponse
    {
        return $this->dispatchTracked($request, 'metaobject-definitions', SyncMetaobjectDefinitionsJob::class, 'Shopify metaobject definitions sync started.');
    }

    public function sellingPlans(Request $request): JsonResponse
    {
        return $this->dispatchTracked($request, 'selling-plans', SyncSellingPlansJob::class, 'Shopify selling plans/subscriptions sync started.');
    }

    public function commerce(Request $request): JsonResponse
    {
        $request->merge(['preset' => 'full_commerce']);
        return $this->bootstrap($request);
    }

    public function bootstrap(Request $request): JsonResponse
    {
        $store = $this->store($request);
        $perPage = (int) $request->input('per_page', 20);
        $perPage = min(max($perPage, 5), 100);

        $preset = $request->input('preset');
        $requestedTypes = $request->input('types', []);

        if (is_string($requestedTypes)) {
            $requestedTypes = [$requestedTypes];
        }

        if (!is_array($requestedTypes)) {
            return response()->json([
                'status' => 'error',
                'message' => 'The types field must be an array of sync type strings.',
            ], 422);
        }

        $presetTypes = $this->presetTypes(is_string($preset) ? $preset : null);
        if ($preset !== null && $presetTypes === null) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid preset. Supported presets are: full_commerce, minimal.',
            ], 422);
        }

        $merged = array_merge($presetTypes ?? [], array_values(array_filter($requestedTypes, 'is_string')));
        if ($merged === []) {
            $merged = $this->presetTypes('full_commerce') ?? [];
            $preset = $preset ?? 'full_commerce';
        }

        $normalized = [];
        foreach ($merged as $type) {
            $type = trim((string) $type);
            if ($type !== '') {
                $normalized[] = $type;
            }
        }

        $selectedSet = array_values(array_unique($normalized));
        $ordered = $this->orderTypesByDependencies($selectedSet);

        $validTypes = array_keys($this->syncMap());
        $invalid = array_values(array_diff($selectedSet, $validTypes));

        if ($ordered === []) {
            return response()->json([
                'status' => 'error',
                'message' => 'No valid sync types were provided.',
                'data' => [
                    'invalid_types' => $invalid,
                    'supported_types' => $validTypes,
                ],
            ], 422);
        }

        $runs = [];
        foreach ($ordered as $type) {
            $runs[] = $this->dispatchType((string) $store->id, $type, $perPage);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Shopify bootstrap sync started.',
            'data' => [
                'store_id' => $store->id,
                'preset' => $preset,
                'requested_types' => $selectedSet,
                'ordered_types' => $ordered,
                'invalid_types' => $invalid,
                'sync_runs' => $runs,
            ],
        ], 202);
    }

    private function dispatchTracked(Request $request, string $type, string $jobClass, string $message): JsonResponse
    {
        $store = $this->store($request);
        $syncRunId = $this->tracker->startRun((string) $store->id, $type);

        $jobClass::dispatch((string) $store->id, $syncRunId);

        return response()->json([
            'status' => 'success',
            'message' => $message,
            'data' => [
                'store_id' => $store->id,
                'sync_run_id' => $syncRunId,
            ],
        ], 202);
    }

    private function syncMap(): array
    {
        return [
            'products' => ['mode' => 'batch', 'job' => SyncProductsPaginationOrchestratorJob::class],
            'shop-details' => ['mode' => 'queue', 'job' => SyncShopifyStoreDetailsJob::class],
            'store-installs' => ['mode' => 'queue', 'job' => SyncStoreInstallsJob::class],
            'files' => ['mode' => 'queue', 'job' => SyncGlobalFilesJob::class],
            'customers' => ['mode' => 'queue', 'job' => SyncCustomersJob::class],
            'orders' => ['mode' => 'queue', 'job' => SyncOrdersJob::class],
            'draft-orders' => ['mode' => 'queue', 'job' => SyncDraftOrdersJob::class],
            'fulfillments' => ['mode' => 'queue', 'job' => SyncFulfillmentsJob::class],
            'financials' => ['mode' => 'queue', 'job' => SyncOrderFinancialsJob::class],
            'discounts' => ['mode' => 'queue', 'job' => SyncDiscountsJob::class],
            'content' => ['mode' => 'queue', 'job' => SyncContentJob::class],
            'webhook-subscriptions' => ['mode' => 'queue', 'job' => SyncWebhookSubscriptionsJob::class],
            'webhook-logs' => ['mode' => 'queue', 'job' => SyncWebhookLogsJob::class],
            'shipping' => ['mode' => 'queue', 'job' => SyncShippingProfilesJob::class],
            'returns-exchanges-reverse' => ['mode' => 'queue', 'job' => SyncReturnsExchangesReverseJob::class],
            'order-risk-channel' => ['mode' => 'queue', 'job' => SyncOrderRiskAndChannelsJob::class],
            'order-duties' => ['mode' => 'queue', 'job' => SyncOrderDutiesJob::class],
            'inventory-states' => ['mode' => 'queue', 'job' => SyncInventoryStatesJob::class],
            'customer-marketing-consent' => ['mode' => 'queue', 'job' => SyncCustomerMarketingConsentJob::class],
            'product-advanced-media' => ['mode' => 'queue', 'job' => SyncProductAdvancedMediaJob::class],
            'markets-price-lists' => ['mode' => 'queue', 'job' => SyncMarketsPriceListsJob::class],
            'metaobject-definitions' => ['mode' => 'queue', 'job' => SyncMetaobjectDefinitionsJob::class],
            'selling-plans' => ['mode' => 'queue', 'job' => SyncSellingPlansJob::class],
        ];
    }

    private function orderTypesByDependencies(array $selectedTypes): array
    {
        $orderedReference = [
            'shop-details',
            'store-installs',
            'products',
            'files',
            'customers',
            'orders',
            'draft-orders',
            'fulfillments',
            'financials',
            'discounts',
            'content',
            'webhook-subscriptions',
            'webhook-logs',
            'shipping',
            'returns-exchanges-reverse',
            'order-risk-channel',
            'order-duties',
            'inventory-states',
            'customer-marketing-consent',
            'product-advanced-media',
            'markets-price-lists',
            'metaobject-definitions',
            'selling-plans',
        ];

        $set = array_flip($selectedTypes);
        $result = [];

        foreach ($orderedReference as $type) {
            if (isset($set[$type]) && isset($this->syncMap()[$type])) {
                $result[] = $type;
            }
        }

        return $result;
    }

    private function presetTypes(?string $preset): ?array
    {
        if ($preset === null) {
            return null;
        }

        return match ($preset) {
            'full_commerce' => [
                'products',
                'shop-details',
                'store-installs',
                'files',
                'customers',
                'orders',
                'draft-orders',
                'fulfillments',
                'financials',
                'discounts',
                'content',
                'webhook-subscriptions',
                'webhook-logs',
                'shipping',
                'returns-exchanges-reverse',
                'order-risk-channel',
                'order-duties',
                'inventory-states',
                'customer-marketing-consent',
                'product-advanced-media',
                'markets-price-lists',
                'metaobject-definitions',
                'selling-plans',
            ],
            'minimal' => [
                'products',
                'shop-details',
                'customers',
                'orders',
                'inventory-states',
            ],
            default => null,
        };
    }

    private function dispatchType(string $storeId, string $type, int $perPage): array
    {
        $config = $this->syncMap()[$type];
        $syncRunId = $this->tracker->startRun($storeId, $type);

        if ($config['mode'] === 'batch') {
            $job = new SyncProductsPaginationOrchestratorJob(
                storeId: $storeId,
                syncRunId: $syncRunId,
                first: $perPage,
                after: null
            );

            $batch = Bus::batch([$job])
                ->name("sync-bootstrap-{$type}:{$storeId}")
                ->dispatch();

            $this->tracker->attachBatch($syncRunId, $batch->id);

            return [
                'type' => $type,
                'sync_run_id' => $syncRunId,
                'batch_id' => $batch->id,
            ];
        }

        $config['job']::dispatch($storeId, $syncRunId);

        return [
            'type' => $type,
            'sync_run_id' => $syncRunId,
        ];
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
