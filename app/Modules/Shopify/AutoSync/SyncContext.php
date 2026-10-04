<?php

namespace App\Modules\Shopify\AutoSync;

use App\Modules\Catalog\Models\Product;
use App\Modules\Shopify\AutoSync\Contracts\EntitySyncer;
use Illuminate\Database\Eloquent\Model;

/**
 * Passed to every syncer: the store gateway plus access to the other syncers.
 */
class SyncContext
{
    /** Products updated inline when a vendor/type/tag change fans out; the rest are queued. */
    private const INLINE_PROPAGATION_LIMIT = 25;

    /** @var array<int, string> */
    private array $notes = [];

    public function __construct(
        public readonly ShopifyGateway $gw,
        private readonly ShopifyAutoSync $autoSync,
    ) {}

    public function syncer(string|Model $model): ?EntitySyncer
    {
        return $this->autoSync->syncerFor($model);
    }

    /**
     * Makes sure a related record exists in Shopify (e.g. the blog of a new article)
     * and returns it refreshed.
     */
    public function ensureCreated(Model $model, string $gidColumn): Model
    {
        if (blank($model->{$gidColumn})) {
            $this->syncer($model)?->create($model, $this);
            $model->refresh();
        }

        return $model;
    }

    /**
     * Re-pushes the given products after a shared attribute (vendor, type, tag...) changed.
     *
     * @param iterable<int|string> $productIds
     * @param array<int, string> $changed
     */
    public function propagateToProducts(iterable $productIds, array $changed): void
    {
        $syncer = $this->syncer(Product::class);
        $count = 0;

        foreach ($productIds as $productId) {
            $product = Product::query()->find($productId);

            if (!$product || blank($product->shopify_product_id)) {
                continue;
            }

            if ($count++ < self::INLINE_PROPAGATION_LIMIT) {
                $syncer->update($product, [], $changed, $this);
                continue;
            }

            $this->autoSync->queue($this->gw->store->id, $product, 'update', [], $changed);
        }

        if ($count > self::INLINE_PROPAGATION_LIMIT) {
            $this->note(($count - self::INLINE_PROPAGATION_LIMIT) . ' product(s) queued for Shopify update.');
        }
    }

    public function note(string $message): void
    {
        $this->notes[] = $message;
    }

    /**
     * @return array<int, string>
     */
    public function notes(): array
    {
        return $this->notes;
    }
}
