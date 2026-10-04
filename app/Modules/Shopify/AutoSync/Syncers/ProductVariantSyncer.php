<?php

namespace App\Modules\Shopify\AutoSync\Syncers;

use App\Modules\Catalog\Models\ProductVariant;
use App\Modules\Shopify\AutoSync\Support\Gid;
use App\Modules\Shopify\AutoSync\SyncContext;
use Illuminate\Database\Eloquent\Model;

class ProductVariantSyncer extends BaseSyncer
{
    private const ALL_FIELDS = ['price', 'compare_at_price', 'barcode', 'taxable', 'sku', 'option_value_ids', 'inventory_quantity'];

    private const BULK_UPDATE = <<<'GQL'
mutation VariantsBulkUpdate($productId: ID!, $variants: [ProductVariantsBulkInput!]!) {
  productVariantsBulkUpdate(productId: $productId, variants: $variants) {
    productVariants { id inventoryItem { id } }
    userErrors { field message }
  }
}
GQL;

    /**
     * @param ProductVariant $model
     */
    public function snapshot(Model $model): array
    {
        return [
            'shopify_variant_id' => $model->shopify_variant_id,
            'shopify_product_id' => $model->product()->value('shopify_product_id'),
        ];
    }

    /**
     * @param ProductVariant $model
     */
    public function create(Model $model, SyncContext $ctx): void
    {
        if (Gid::make($model->shopify_variant_id, 'ProductVariant')) {
            $this->update($model, [], self::ALL_FIELDS, $ctx);
            return;
        }

        $product = $ctx->ensureCreated($model->product, 'shopify_product_id');
        $productGid = Gid::make($product->shopify_product_id, 'Product');

        if (!$productGid) {
            return;
        }

        $fields = $this->fields($model, null);
        $optionValues = $this->optionValues($model);
        $targetGid = $this->findReusableVariant($model, $productGid, $optionValues, $ctx);

        if ($targetGid) {
            $result = $ctx->gw->mutate(self::BULK_UPDATE, [
                'productId' => $productGid,
                'variants' => [['id' => $targetGid] + $fields],
            ], 'productVariantsBulkUpdate');
        } else {
            $result = $ctx->gw->mutate(<<<'GQL'
mutation VariantsBulkCreate($productId: ID!, $variants: [ProductVariantsBulkInput!]!) {
  productVariantsBulkCreate(productId: $productId, variants: $variants) {
    productVariants { id inventoryItem { id } }
    userErrors { field message }
  }
}
GQL, [
                'productId' => $productGid,
                'variants' => [$fields + ['optionValues' => $optionValues ?: [['optionName' => 'Title', 'name' => (string) $model->title]]]],
            ], 'productVariantsBulkCreate');
        }

        $created = $result['productVariants'][0] ?? [];
        $this->persist($model, ['shopify_variant_id' => $created['id'] ?? null]);

        if ($model->inventory_quantity !== null) {
            $this->setQuantity($created['inventoryItem']['id'] ?? null, (int) $model->inventory_quantity, $ctx);
        }
    }

    /**
     * @param ProductVariant $model
     */
    public function update(Model $model, array $before, array $changed, SyncContext $ctx): void
    {
        $gid = Gid::make($model->shopify_variant_id, 'ProductVariant');
        $productGid = Gid::make($model->product()->value('shopify_product_id'), 'Product');

        if (!$gid || !$productGid) {
            $this->create($model, $ctx);
            return;
        }

        $input = $this->fields($model, $changed);

        if (in_array('option_value_ids', $changed, true) && ($optionValues = $this->optionValues($model)) !== []) {
            $input['optionValues'] = $optionValues;
        }

        $inventoryItemId = null;

        if ($input !== []) {
            $result = $ctx->gw->mutate(self::BULK_UPDATE, [
                'productId' => $productGid,
                'variants' => [['id' => $gid] + $input],
            ], 'productVariantsBulkUpdate');
            $inventoryItemId = $result['productVariants'][0]['inventoryItem']['id'] ?? null;
        }

        if (in_array('inventory_quantity', $changed, true) && $model->inventory_quantity !== null) {
            $inventoryItemId ??= $ctx->gw->query(
                'query VariantItem($id: ID!) { productVariant(id: $id) { inventoryItem { id } } }',
                ['id' => $gid],
            )['productVariant']['inventoryItem']['id'] ?? null;

            $this->setQuantity($inventoryItemId, (int) $model->inventory_quantity, $ctx);
        }
    }

    public function delete(array $snapshot, SyncContext $ctx): void
    {
        $gid = Gid::make($snapshot['shopify_variant_id'] ?? null, 'ProductVariant');
        $productGid = Gid::make($snapshot['shopify_product_id'] ?? null, 'Product');

        if (!$gid || !$productGid) {
            return;
        }

        $this->ignoreNotFound(fn () => $ctx->gw->mutate(<<<'GQL'
mutation VariantsBulkDelete($productId: ID!, $variantsIds: [ID!]!) {
  productVariantsBulkDelete(productId: $productId, variantsIds: $variantsIds) {
    product { id }
    userErrors { field message }
  }
}
GQL, ['productId' => $productGid, 'variantsIds' => [$gid]], 'productVariantsBulkDelete'));
    }

    /**
     * @param array<int, string>|null $changed
     * @return array<string, mixed>
     */
    private function fields(ProductVariant $model, ?array $changed): array
    {
        $input = [];

        if ($this->touched($changed, 'price') && $model->price !== null) {
            $input['price'] = (string) $model->price;
        }
        if ($this->touched($changed, 'compare_at_price')) {
            $input['compareAtPrice'] = $model->compare_at_price !== null ? (string) $model->compare_at_price : null;
        }
        if ($this->touched($changed, 'barcode')) {
            $input['barcode'] = $model->barcode;
        }
        if ($this->touched($changed, 'taxable') && $model->taxable !== null) {
            $input['taxable'] = (bool) $model->taxable;
        }
        if ($this->touched($changed, 'sku')) {
            $input['inventoryItem'] = ['sku' => $model->sku] + ($changed === null ? ['tracked' => true] : []);
        }

        return $input;
    }

    /**
     * @return array<int, array<string, string>>
     */
    private function optionValues(ProductVariant $model): array
    {
        return $model->optionValues()->with('option')->get()
            ->filter(fn ($value) => filled($value->option?->name))
            ->map(fn ($value) => [
                'optionName' => (string) $value->option->name,
                'name' => (string) ($value->label ?: $value->value),
            ])
            ->values()
            ->all();
    }

    /**
     * A new product in Shopify already has one variant (the default one, or the first
     * option combination). Reuse it instead of creating a duplicate.
     *
     * @param array<int, array<string, string>> $optionValues
     */
    private function findReusableVariant(ProductVariant $model, string $productGid, array $optionValues, SyncContext $ctx): ?string
    {
        $product = $ctx->gw->query(<<<'GQL'
query ProductVariants($id: ID!) {
  product(id: $id) {
    hasOnlyDefaultVariant
    variants(first: 100) { nodes { id selectedOptions { name value } } }
  }
}
GQL, ['id' => $productGid])['product'] ?? [];

        $variants = $product['variants']['nodes'] ?? [];
        $mapped = ProductVariant::query()
            ->where('product_id', $model->product_id)
            ->whereKeyNot($model->getKey())
            ->whereNotNull('shopify_variant_id')
            ->pluck('shopify_variant_id')
            ->map(fn ($id) => Gid::make($id, 'ProductVariant'))
            ->all();

        $unmapped = array_values(array_filter($variants, fn ($variant) => !in_array($variant['id'], $mapped, true)));

        if (!empty($product['hasOnlyDefaultVariant']) && $unmapped !== []) {
            if ($optionValues !== []) {
                $grouped = collect($optionValues)->groupBy('optionName')
                    ->map(fn ($values, $name) => ['name' => $name, 'values' => $values->map(fn ($v) => ['name' => $v['name']])->unique('name')->values()->all()])
                    ->values()
                    ->all();

                $ctx->gw->mutate(<<<'GQL'
mutation ProductOptionsCreate($productId: ID!, $options: [OptionCreateInput!]!) {
  productOptionsCreate(productId: $productId, options: $options, variantStrategy: LEAVE_AS_IS) {
    product { id }
    userErrors { field message code }
  }
}
GQL, ['productId' => $productGid, 'options' => $grouped], 'productOptionsCreate');
            }

            return $unmapped[0]['id'];
        }

        $wanted = collect($optionValues)->mapWithKeys(fn ($v) => [$v['optionName'] => $v['name']])->sortKeys()->all();

        foreach ($unmapped as $variant) {
            $selected = collect($variant['selectedOptions'] ?? [])->mapWithKeys(fn ($o) => [$o['name'] => $o['value']])->sortKeys()->all();

            if ($wanted !== [] && $selected === $wanted) {
                return $variant['id'];
            }
        }

        return null;
    }

    private function setQuantity(?string $inventoryItemId, int $quantity, SyncContext $ctx): void
    {
        $locationId = $ctx->gw->primaryLocationId();

        if ($inventoryItemId && $locationId) {
            $ctx->gw->setAvailableQuantity($inventoryItemId, $locationId, $quantity);
        }
    }
}
