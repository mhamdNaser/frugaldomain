<?php

namespace App\Modules\Shopify\AutoSync;

use App\Modules\Catalog\Models\Collection;
use App\Modules\Catalog\Models\Option;
use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\ProductType;
use App\Modules\Catalog\Models\ProductVariant;
use App\Modules\Catalog\Models\Tag;
use App\Modules\Catalog\Models\Vendor;
use App\Modules\CMS\Models\Article;
use App\Modules\CMS\Models\Blog;
use App\Modules\CMS\Models\Menu;
use App\Modules\CMS\Models\Metafield;
use App\Modules\CMS\Models\MetaObject;
use App\Modules\CMS\Models\Page;
use App\Modules\Inventory\Models\InventoryLevel;
use App\Modules\Marketing\Models\Discount;
use App\Modules\Marketing\Models\DiscountCode;
use App\Modules\Orders\Models\Order;
use App\Modules\Shipping\Models\ShippingZone;
use App\Modules\Shopify\AutoSync\Contracts\EntitySyncer;
use App\Modules\Shopify\AutoSync\Exceptions\ShopifyUserErrorsException;
use App\Modules\Shopify\AutoSync\Syncers;
use App\Modules\Shopify\Exceptions\ShopifySyncException;
use App\Modules\Shopify\OutboundSync\DTOs\EnqueueOutboundSyncData;
use App\Modules\Shopify\OutboundSync\Jobs\ProcessOutboundSyncJob;
use App\Modules\Shopify\OutboundSync\Services\OutboundSyncManager;
use App\Modules\Stores\Models\Store;
use App\Modules\User\Models\Customer;
use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

/**
 * Pushes every dashboard create/update/delete to Shopify inside the same request.
 *
 * - Local change + Shopify call run in one DB transaction: if Shopify rejects the data
 *   (userErrors / GraphQL errors) the local change is rolled back and a 422 is returned.
 * - If Shopify is unreachable (timeout, 429, 5xx) the local change is kept and the push
 *   is queued in shopify_outbound_syncs to be retried automatically.
 */
class ShopifyAutoSync
{
    /** @var array<class-string<Model>, class-string<EntitySyncer>> */
    private const SYNCERS = [
        Product::class => Syncers\ProductSyncer::class,
        ProductVariant::class => Syncers\ProductVariantSyncer::class,
        Collection::class => Syncers\CollectionSyncer::class,
        Vendor::class => Syncers\VendorSyncer::class,
        ProductType::class => Syncers\ProductTypeSyncer::class,
        Tag::class => Syncers\TagSyncer::class,
        Option::class => Syncers\OptionSyncer::class,
        InventoryLevel::class => Syncers\InventoryLevelSyncer::class,
        Blog::class => Syncers\BlogSyncer::class,
        Article::class => Syncers\ArticleSyncer::class,
        Page::class => Syncers\PageSyncer::class,
        Menu::class => Syncers\MenuSyncer::class,
        Metafield::class => Syncers\MetafieldSyncer::class,
        MetaObject::class => Syncers\MetaobjectSyncer::class,
        Customer::class => Syncers\CustomerSyncer::class,
        Discount::class => Syncers\DiscountSyncer::class,
        DiscountCode::class => Syncers\DiscountCodeSyncer::class,
        ShippingZone::class => Syncers\ShippingZoneSyncer::class,
        Order::class => Syncers\OrderSyncer::class,
    ];

    /** @var array<string, mixed> */
    private array $report = ['status' => 'skipped', 'message' => null, 'notes' => []];

    public function __construct(
        private readonly OutboundSyncManager $outboundSyncManager,
    ) {}

    /**
     * @template T of Model
     * @param Closure(): T $persist
     * @return T
     */
    public function create(Closure $persist): Model
    {
        return DB::transaction(function () use ($persist) {
            $model = $persist();
            $this->push($model, 'create', [], []);

            return $model;
        });
    }

    /**
     * @template T of Model
     * @param array<int, string> $changed keys of the validated request
     * @param Closure(): T $persist
     * @return T
     */
    public function update(Model $current, array $changed, Closure $persist): Model
    {
        return DB::transaction(function () use ($current, $changed, $persist) {
            $before = $this->syncerFor($current)?->snapshot($current) ?? [];
            $model = $persist();
            $this->push($model, 'update', $before, array_values($changed));

            return $model;
        });
    }

    public function delete(Model $current, Closure $persist): void
    {
        DB::transaction(function () use ($current, $persist) {
            $snapshot = $this->syncerFor($current)?->snapshot($current) ?? [];
            $persist();
            $this->push($current, 'delete', $snapshot, []);
        });
    }

    /**
     * Runs an ad-hoc Shopify push (e.g. attaching media). Shopify data errors still
     * abort with 422; connectivity errors are reported but not retried.
     *
     * @param Closure(SyncContext): void $push
     */
    public function run(?string $storeId, Closure $push): void
    {
        $this->execute($storeId, $push, null);
    }

    /**
     * @return array<string, mixed>
     */
    public function report(): array
    {
        return $this->report;
    }

    public function syncerFor(string|Model $model): ?EntitySyncer
    {
        $class = $model instanceof Model ? $model::class : $model;
        $syncer = self::SYNCERS[$class] ?? null;

        return $syncer ? app($syncer) : null;
    }

    public function gatewayFor(?string $storeId): ?ShopifyGateway
    {
        if (blank($storeId)) {
            return null;
        }

        $store = Store::query()->find($storeId);

        if (!$store || blank($store->shopify_domain) || blank($store->shopify_access_token)) {
            return null;
        }

        return new ShopifyGateway($store);
    }

    /**
     * Queues a push to be retried by the outbound worker.
     *
     * @param array<string, mixed> $before
     * @param array<int, string> $changed
     */
    public function queue(string $storeId, Model $model, string $action, array $before, array $changed): int
    {
        $id = $this->outboundSyncManager->enqueue(new EnqueueOutboundSyncData(
            storeId: $storeId,
            entityType: class_basename($model),
            entityId: (string) $model->getKey(),
            action: $action,
            handler: AutoSyncOutboundHandler::class,
            payload: [
                'auto_sync' => [
                    'model' => $model::class,
                    'id' => $model->getKey(),
                    'action' => $action,
                    'before' => $before,
                    'changed' => $changed,
                ],
            ],
            idempotencyKey: hash('sha256', implode('|', [$model::class, $model->getKey(), $action, json_encode($changed), microtime(true)])),
            maxAttempts: 8,
        ));

        ProcessOutboundSyncJob::dispatch($id)->afterCommit();

        return $id;
    }

    /**
     * @param array<string, mixed> $before
     * @param array<int, string> $changed
     */
    private function push(Model $model, string $action, array $before, array $changed): void
    {
        $syncer = $this->syncerFor($model);

        if (!$syncer) {
            $this->report = ['status' => 'not_applicable', 'message' => null, 'notes' => []];
            return;
        }

        $this->execute(
            (string) $model->getAttribute('store_id'),
            function (SyncContext $ctx) use ($syncer, $model, $action, $before, $changed) {
                match ($action) {
                    'create' => $syncer->create($model, $ctx),
                    'update' => $syncer->update($model, $before, $changed, $ctx),
                    'delete' => $syncer->delete($before, $ctx),
                };
            },
            fn () => $this->queue((string) $model->getAttribute('store_id'), $model, $action, $before, $changed),
        );
    }

    /**
     * @param Closure(SyncContext): void $push
     * @param (Closure(): int)|null $queue
     */
    private function execute(?string $storeId, Closure $push, ?Closure $queue): void
    {
        $gateway = $this->gatewayFor($storeId);

        if (!$gateway) {
            $this->report = ['status' => 'skipped', 'message' => 'Store is not connected to Shopify.', 'notes' => []];
            return;
        }

        $ctx = new SyncContext($gateway, $this);

        try {
            $push($ctx);
            $this->report = ['status' => 'synced', 'message' => null, 'notes' => $ctx->notes()];
        } catch (ShopifyUserErrorsException $e) {
            throw ValidationException::withMessages(['shopify' => $e->getMessage()]);
        } catch (ShopifySyncException $e) {
            if (!self::isRetryable($e)) {
                throw ValidationException::withMessages(['shopify' => $e->getMessage()]);
            }

            Log::warning('Shopify auto-sync: Shopify unreachable', ['store_id' => $storeId, 'error' => $e->getMessage()]);

            if ($queue) {
                $outboundSyncId = $queue();
                $this->report = [
                    'status' => 'queued',
                    'message' => 'Shopify is temporarily unreachable; the change was saved and will be pushed automatically.',
                    'outbound_sync_id' => $outboundSyncId,
                    'notes' => $ctx->notes(),
                ];
                return;
            }

            $this->report = ['status' => 'failed', 'message' => $e->getMessage(), 'notes' => $ctx->notes()];
        }
    }

    public static function isRetryable(\Throwable $e): bool
    {
        $previous = $e->getPrevious();

        if ($previous instanceof ConnectionException) {
            return true;
        }

        $status = $previous instanceof RequestException
            ? $previous->response->status()
            : (int) (($e instanceof ShopifySyncException ? $e->getContext()['status'] ?? 0 : 0));

        if ($status === 429 || $status >= 500) {
            return true;
        }

        return str_contains(strtolower($e->getMessage()), 'throttled');
    }
}
