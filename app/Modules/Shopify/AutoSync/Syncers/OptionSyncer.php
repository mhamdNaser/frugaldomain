<?php

namespace App\Modules\Shopify\AutoSync\Syncers;

use App\Modules\Catalog\Models\Option;
use App\Modules\Catalog\Models\Product;
use App\Modules\Shopify\AutoSync\Support\Gid;
use App\Modules\Shopify\AutoSync\SyncContext;
use Illuminate\Database\Eloquent\Model;

/**
 * Options are per product in Shopify. A global option change is applied to every
 * linked product that already exists in Shopify.
 */
class OptionSyncer extends BaseSyncer
{
    /**
     * @param Option $model
     */
    public function snapshot(Model $model): array
    {
        return [
            'name' => $model->name,
            'product_ids' => $model->products()->pluck('products.id')->all(),
        ];
    }

    public function create(Model $model, SyncContext $ctx): void
    {
        $ctx->note('Option saved. Shopify will show it once it is assigned to a product.');
    }

    /**
     * @param Option $model
     */
    public function update(Model $model, array $before, array $changed, SyncContext $ctx): void
    {
        $oldName = (string) ($before['name'] ?? $model->name);
        $valueNames = $model->values()->get()
            ->map(fn ($value) => trim((string) ($value->label ?: $value->value)))
            ->filter()
            ->unique()
            ->values()
            ->all();

        foreach ($this->linkedProductGids($before['product_ids'] ?? []) as $productGid) {
            $shopifyOption = $this->findOption($productGid, $oldName, $ctx);

            if (!$shopifyOption) {
                continue;
            }

            $existing = collect($shopifyOption['optionValues'] ?? [])->pluck('name')->all();
            $toAdd = array_values(array_diff($valueNames, $existing));
            $renamed = $oldName !== (string) $model->name;

            if (!$renamed && $toAdd === []) {
                continue;
            }

            $ctx->gw->mutate(<<<'GQL'
mutation ProductOptionUpdate($productId: ID!, $option: OptionUpdateInput!, $optionValuesToAdd: [OptionValueCreateInput!]) {
  productOptionUpdate(productId: $productId, option: $option, optionValuesToAdd: $optionValuesToAdd, variantStrategy: LEAVE_AS_IS) {
    product { id }
    userErrors { field message code }
  }
}
GQL, [
                'productId' => $productGid,
                'option' => ['id' => $shopifyOption['id'], 'name' => (string) $model->name],
                'optionValuesToAdd' => array_map(fn ($name) => ['name' => $name], $toAdd),
            ], 'productOptionUpdate');
        }
    }

    public function delete(array $snapshot, SyncContext $ctx): void
    {
        foreach ($this->linkedProductGids($snapshot['product_ids'] ?? []) as $productGid) {
            $shopifyOption = $this->findOption($productGid, (string) ($snapshot['name'] ?? ''), $ctx);

            if (!$shopifyOption) {
                continue;
            }

            $ctx->gw->mutate(<<<'GQL'
mutation ProductOptionsDelete($productId: ID!, $options: [ID!]!) {
  productOptionsDelete(productId: $productId, options: $options, strategy: NON_DESTRUCTIVE) {
    deletedOptionsIds
    userErrors { field message code }
  }
}
GQL, ['productId' => $productGid, 'options' => [$shopifyOption['id']]], 'productOptionsDelete');
        }
    }

    /**
     * @param array<int, int> $productIds
     * @return array<int, string>
     */
    private function linkedProductGids(array $productIds): array
    {
        return Product::query()
            ->whereIn('id', $productIds)
            ->whereNotNull('shopify_product_id')
            ->pluck('shopify_product_id')
            ->map(fn ($id) => Gid::make($id, 'Product'))
            ->filter()
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>|null
     */
    private function findOption(string $productGid, string $name, SyncContext $ctx): ?array
    {
        $options = $ctx->gw->query(
            'query ProductOptions($id: ID!) { product(id: $id) { options { id name optionValues { id name } } } }',
            ['id' => $productGid],
        )['product']['options'] ?? [];

        foreach ($options as $option) {
            if (strcasecmp((string) ($option['name'] ?? ''), $name) === 0) {
                return $option;
            }
        }

        return null;
    }
}
