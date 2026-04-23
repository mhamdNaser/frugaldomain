<?php

namespace App\Modules\Shopify\Services\Sync;

use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\Option;
use App\Modules\Catalog\Models\OptionValue;
use App\Modules\Stores\Models\Store;

class OptionSyncService
{
    public function sync(Store $store, Product $product, array $options): array
    {
        $optionValueMap = [];

        foreach ($options as $position => $optionData) {
            $name = $this->optionName($optionData);

            if (!$name) {
                continue;
            }

            $option = Option::query()->firstOrCreate([
                'store_id' => $store->id,
                'name' => $name,
            ]);

            $product->options()->syncWithoutDetaching([
                $option->id => [
                    'store_id' => $store->id,
                    'position' => $this->optionPosition($optionData, $position + 1),
                ]
            ]);

            foreach ($this->optionValues($optionData) as $valueData) {

                $label = $valueData['name'] ?? null;

                if (!$label) {
                    continue;
                }

                $value = $valueData['swatch']['color']
                    ?? $valueData['swatch']['image']['image']['url']
                    ?? $label;

                $optionValue = OptionValue::updateOrCreate(
                    [
                        'option_id' => $option->id,
                        'value' => $value,
                    ],
                    [
                        'label' => $label,
                    ]
                );

                $optionValueMap[$option->name]['by_value'][$value] = $optionValue->id;
                $optionValueMap[$option->name]['by_label'][$label] = $optionValue->id;
            }
        }

        return $optionValueMap;
    }

    private function optionName(mixed $optionData): ?string
    {
        return is_array($optionData)
            ? ($optionData['name'] ?? null)
            : ($optionData->name ?? null);
    }

    private function optionPosition(mixed $optionData, int $fallback): int
    {
        return (int) (is_array($optionData)
            ? ($optionData['position'] ?? $fallback)
            : ($optionData->position ?? $fallback));
    }

    private function optionValues(mixed $optionData): array
    {
        return is_array($optionData)
            ? ($optionData['optionValues'] ?? [])
            : ($optionData->optionValues ?? []);
    }
}
