<?php

namespace App\Modules\Shopify\AutoSync\Syncers;

use App\Modules\Catalog\Models\ProductVariant;
use App\Modules\Inventory\Models\InventoryLevel;
use App\Modules\Shopify\AutoSync\Support\Gid;
use App\Modules\Shopify\AutoSync\SyncContext;
use Illuminate\Database\Eloquent\Model;

class InventoryLevelSyncer extends BaseSyncer
{
    /**
     * @param InventoryLevel $model
     */
    public function create(Model $model, SyncContext $ctx): void
    {
        $this->pushAvailable($model, $ctx);
    }

    /**
     * @param InventoryLevel $model
     */
    public function update(Model $model, array $before, array $changed, SyncContext $ctx): void
    {
        if (array_intersect(['available', 'on_hand', 'shopify_location_id', 'inventory_item_id', 'product_variant_id'], $changed) !== []) {
            $this->pushAvailable($model, $ctx);
        }
    }

    /**
     * Removing a local stock row does not deactivate the item at the Shopify location
     * (that would hide the stock history); nothing is pushed.
     */
    public function delete(array $snapshot, SyncContext $ctx): void
    {
        $ctx->note('Local inventory row removed; Shopify stock levels were left unchanged.');
    }

    private function pushAvailable(InventoryLevel $model, SyncContext $ctx): void
    {
        $inventoryItemId = Gid::make($model->inventory_item_id, 'InventoryItem') ?? $this->resolveInventoryItem($model, $ctx);
        $locationId = Gid::make($model->shopify_location_id, 'Location') ?? $ctx->gw->primaryLocationId();

        if (!$inventoryItemId || !$locationId) {
            $ctx->note('Inventory not pushed: the variant is not in Shopify yet.');
            return;
        }

        $this->persist($model, [
            'inventory_item_id' => blank($model->inventory_item_id) ? $inventoryItemId : null,
            'shopify_location_id' => blank($model->shopify_location_id) ? $locationId : null,
        ]);

        $ctx->gw->setAvailableQuantity($inventoryItemId, $locationId, (int) $model->available);
    }

    private function resolveInventoryItem(InventoryLevel $model, SyncContext $ctx): ?string
    {
        $variant = ProductVariant::query()->find($model->product_variant_id);

        if (!$variant) {
            return null;
        }

        $ctx->ensureCreated($variant, 'shopify_variant_id');
        $variantGid = Gid::make($variant->shopify_variant_id, 'ProductVariant');

        if (!$variantGid) {
            return null;
        }

        return $ctx->gw->query(
            'query VariantItem($id: ID!) { productVariant(id: $id) { inventoryItem { id } } }',
            ['id' => $variantGid],
        )['productVariant']['inventoryItem']['id'] ?? null;
    }
}
