<?php

namespace App\Modules\Shopify\Services\Sync;

use App\Modules\Inventory\Models\Inventory;
use App\Modules\Inventory\Models\InventoryLevel;
use App\Modules\Inventory\Models\Location;
use App\Modules\Inventory\Models\InventoryMovement;
use App\Modules\Catalog\Models\ProductVariant;
use App\Modules\Shopify\DTOs\InventoryData;
use App\Modules\Stores\Models\Store;

class InventorySyncService
{
    public function syncVariantInventory(
        Store $store,
        ProductVariant $variant,
        InventoryData $inventoryData,
        ?string $referenceType = 'shopify_sync',
        ?string $referenceId = null
    ): void {

        if (empty($inventoryData->inventoryItemId)) {
            return;
        }

        $locations = $inventoryData->locations ?: [[
            'id' => null,
            'name' => null,
            'quantity' => $inventoryData->quantity,
        ]];

        foreach ($locations as $locationData) {
            $location = $this->syncLocation($store, $locationData);
            $quantity = (int) ($locationData['quantity'] ?? $inventoryData->quantity);

            $this->syncInventoryLevel($store, $variant, $inventoryData, $locationData, $quantity);

            $inventory = Inventory::query()->firstOrNew([
                'store_id' => $store->id,
                'product_variant_id' => $variant->id,
                'location_id' => $location?->id,
            ]);

            $oldAvailable = (int) ($inventory->available_quantity ?? 0);

            $inventory->fill([
                'shopify_inventory_item_id' => $inventoryData->inventoryItemId,
                'tracked' => $inventoryData->tracked,
                'requires_shipping' => $inventoryData->requiresShipping,
                'weight' => $inventoryData->weight ?? null,
                'weight_unit' => $inventoryData->weightUnit ?? null,
                'available_quantity' => $quantity,
            ]);

            $inventory->save();

            $difference = $quantity - $oldAvailable;

            if (!$inventory->wasRecentlyCreated && $difference === 0) {
                continue;
            }

            InventoryMovement::query()->create([
                'store_id' => $store->id,
                'product_variant_id' => $variant->id,
                'location_id' => $location?->id,
                'type' => 'adjustment',
                'quantity' => $inventory->wasRecentlyCreated ? $quantity : $difference,
                'before_quantity' => $oldAvailable,
                'after_quantity' => $quantity,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
            ]);
        }
    }

    private function syncInventoryLevel(
        Store $store,
        ProductVariant $variant,
        InventoryData $inventoryData,
        array $locationData,
        int $quantity
    ): void {
        InventoryLevel::query()->updateOrCreate(
            [
                'store_id' => $store->id,
                'inventory_item_id' => $inventoryData->inventoryItemId,
                'shopify_location_id' => $locationData['id'] ?? null,
            ],
            [
                'product_variant_id' => $variant->id,
                'available' => $quantity,
                'shopify_updated_at' => $locationData['updated_at'] ?? null,
                'raw_payload' => $locationData['raw_payload'] ?? [
                    'location' => [
                        'id' => $locationData['id'] ?? null,
                        'name' => $locationData['name'] ?? null,
                    ],
                    'available' => $quantity,
                ],
            ]
        );
    }

    private function syncLocation(Store $store, array $locationData): ?Location
    {
        if (empty($locationData['id'])) {
            return null;
        }

        return Location::query()->updateOrCreate(
            [
                'store_id' => $store->id,
                'shopify_location_id' => $locationData['id'],
            ],
            [
                'name' => $locationData['name'] ?? 'Shopify Location',
                'is_active' => true,
            ]
        );
    }
}
