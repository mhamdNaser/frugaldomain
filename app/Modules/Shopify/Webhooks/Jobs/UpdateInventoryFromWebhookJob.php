<?php

namespace App\Modules\Shopify\Webhooks\Jobs;

use App\Modules\Catalog\Models\ProductVariant;
use App\Modules\Inventory\Models\Inventory;
use App\Modules\Shopify\DTOs\InventoryData;
use App\Modules\Shopify\Services\Sync\InventorySyncService;
use App\Modules\Stores\Models\Store;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class UpdateInventoryFromWebhookJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 120;

    public function __construct(
        public readonly string $storeId,
        public readonly string $inventoryItemId,
        public readonly ?string $locationId,
        public readonly int $available,
        public readonly ?string $updatedAt = null,
        public readonly ?string $webhookExternalId = null,
    ) {
        $this->onQueue('shopify-inventory');
    }

    public function handle(InventorySyncService $syncService): void
    {
        $store = Store::query()->findOrFail($this->storeId);

        $inventoryItemGid = $this->toGid('InventoryItem', $this->inventoryItemId);

        /** @var ProductVariant|null $variant */
        $variant = ProductVariant::query()
            ->where('store_id', $store->id)
            ->where('raw_payload->inventoryItem->id', $inventoryItemGid)
            ->first();

        if (!$variant) {
            return;
        }

        $locations = [[
            'id' => $this->locationId ? $this->toGid('Location', $this->locationId) : null,
            'name' => null,
            'quantity' => $this->available,
            'updated_at' => $this->updatedAt,
            'raw_payload' => [
                'inventory_item_id' => $this->inventoryItemId,
                'location_id' => $this->locationId,
                'available' => $this->available,
                'updated_at' => $this->updatedAt,
            ],
        ]];

        $inventoryData = new InventoryData(
            inventoryItemId: $inventoryItemGid,
            tracked: false,
            requiresShipping: false,
            weight: null,
            weightUnit: 'KILOGRAMS',
            locations: $locations,
            quantity: $this->available,
            rawPayload: null,
        );

        $syncService->syncVariantInventory(
            store: $store,
            variant: $variant,
            inventoryData: $inventoryData,
            referenceType: 'shopify_webhook',
            referenceId: $this->webhookExternalId,
        );

        $total = (int) Inventory::query()
            ->where('store_id', $store->id)
            ->where('product_variant_id', $variant->id)
            ->sum('available_quantity');

        $variant->update(['inventory_quantity' => $total]);
    }

    private function toGid(string $type, string $id): string
    {
        if (str_starts_with($id, 'gid://')) {
            return $id;
        }

        return 'gid://shopify/' . $type . '/' . trim($id);
    }
}

