<?php

namespace App\Modules\Shopify\AutoSync\Syncers;

use App\Modules\Catalog\Models\Collection;
use App\Modules\Catalog\Models\Product;
use App\Modules\Shopify\AutoSync\Support\Gid;
use App\Modules\Shopify\AutoSync\SyncContext;
use Illuminate\Database\Eloquent\Model;

class ProductSyncer extends BaseSyncer
{
    private const ALL_FIELDS = ['title', 'description', 'handle', 'vendor_id', 'product_type_id', 'category_id', 'tag_ids', 'tags', 'status', 'seo_title', 'seo_description', 'collection_ids'];

    /**
     * @param Product $model
     */
    public function snapshot(Model $model): array
    {
        return [
            'shopify_product_id' => $model->shopify_product_id,
            'collection_ids' => $model->collections()->pluck('collections.id')->map(fn ($id) => (int) $id)->all(),
            'option_names' => $model->options()->pluck('name')->filter()->values()->all(),
        ];
    }

    /**
     * @param Product $model
     */
    public function create(Model $model, SyncContext $ctx): void
    {
        if (Gid::make($model->shopify_product_id, 'Product')) {
            $this->update($model, $this->snapshot($model), self::ALL_FIELDS, $ctx);
            return;
        }

        $input = $this->fields($model, null);
        $input['collectionsToJoin'] = $this->collectionGids($model->collections()->get(), $ctx);

        $options = $this->productOptions($model);
        if ($options !== []) {
            $input['productOptions'] = $options;
        }

        $result = $ctx->gw->mutate(<<<'GQL'
mutation ProductCreate($product: ProductCreateInput!) {
  productCreate(product: $product) {
    product { id handle }
    userErrors { field message }
  }
}
GQL, ['product' => array_filter($input, fn ($value) => !in_array($value, [[], null, ''], true))], 'productCreate');

        $gid = (string) ($result['product']['id'] ?? '');

        $this->persist($model, [
            'shopify_product_id' => $gid,
            'handle' => blank($model->handle) ? ($result['product']['handle'] ?? null) : null,
        ]);

        if ($gid !== '' && strtolower((string) $model->status) === 'active') {
            $ctx->gw->publish($gid);
        }
    }

    /**
     * @param Product $model
     */
    public function update(Model $model, array $before, array $changed, SyncContext $ctx): void
    {
        $gid = Gid::make($model->shopify_product_id, 'Product');

        if (!$gid) {
            $this->create($model, $ctx);
            return;
        }

        $input = $this->fields($model, $changed);

        if (in_array('collection_ids', $changed, true) && array_key_exists('collection_ids', $before)) {
            $current = $model->collections()->get();
            $currentIds = $current->pluck('id')->map(fn ($id) => (int) $id)->all();
            $removedIds = array_diff($before['collection_ids'], $currentIds);
            $addedIds = array_diff($currentIds, $before['collection_ids']);

            $join = $this->collectionGids($current->whereIn('id', $addedIds), $ctx);
            $leave = $this->collectionGids(Collection::query()->whereIn('id', $removedIds)->get(), $ctx, createMissing: false);

            if ($join !== []) {
                $input['collectionsToJoin'] = $join;
            }
            if ($leave !== []) {
                $input['collectionsToLeave'] = $leave;
            }
        }

        if ($input !== []) {
            $ctx->gw->mutate(<<<'GQL'
mutation ProductUpdate($product: ProductUpdateInput!) {
  productUpdate(product: $product) {
    product { id }
    userErrors { field message }
  }
}
GQL, ['product' => ['id' => $gid] + $input], 'productUpdate');
        }

        if (in_array('option_ids', $changed, true) && array_key_exists('option_names', $before)) {
            $this->syncOptions($model, $gid, $before['option_names'], $ctx);
        }

        if (in_array('status', $changed, true) && strtolower((string) $model->status) === 'active') {
            $ctx->gw->publish($gid);
        }
    }

    public function delete(array $snapshot, SyncContext $ctx): void
    {
        $gid = Gid::make($snapshot['shopify_product_id'] ?? null, 'Product');

        if (!$gid) {
            return;
        }

        $this->ignoreNotFound(fn () => $ctx->gw->mutate(<<<'GQL'
mutation ProductDelete($input: ProductDeleteInput!) {
  productDelete(input: $input) {
    deletedProductId
    userErrors { field message }
  }
}
GQL, ['input' => ['id' => $gid]], 'productDelete'));
    }

    /**
     * @param array<int, string>|null $changed
     * @return array<string, mixed>
     */
    private function fields(Product $model, ?array $changed): array
    {
        $input = [];

        if ($this->touched($changed, 'title')) {
            $input['title'] = (string) $model->title;
        }
        if ($this->touched($changed, 'description')) {
            $input['descriptionHtml'] = (string) ($model->description ?? '');
        }
        if ($this->touched($changed, 'handle') && filled($model->handle)) {
            $input['handle'] = (string) $model->handle;
        }
        if ($this->touched($changed, 'vendor_id')) {
            $input['vendor'] = (string) ($model->vendor()->value('name') ?? '');
        }
        if ($this->touched($changed, 'product_type_id')) {
            $input['productType'] = (string) ($model->productType()->value('name') ?? '');
        }
        if ($this->touched($changed, 'category_id')) {
            $category = $this->categoryGid($model->category()->value('shopify_category_id'));
            if ($category || $changed !== null) {
                $input['category'] = $category;
            }
        }
        if ($this->touched($changed, 'tag_ids', 'tags')) {
            $relationTags = $model->tags()->pluck('name')->all();
            $input['tags'] = $this->tagList($changed !== null && in_array('tag_ids', $changed, true)
                ? $relationTags
                : array_merge($relationTags, $this->tagList($model->tags)));
        }
        if ($this->touched($changed, 'status')) {
            $status = strtoupper((string) $model->status);
            $input['status'] = in_array($status, ['ACTIVE', 'DRAFT', 'ARCHIVED'], true) ? $status : 'DRAFT';
        }
        if ($this->touched($changed, 'seo_title', 'seo_description')) {
            $input['seo'] = ['title' => $model->seo_title, 'description' => $model->seo_description];
        }

        return $input;
    }

    /**
     * @param iterable<Collection> $collections
     * @return array<int, string>
     */
    private function collectionGids(iterable $collections, SyncContext $ctx, bool $createMissing = true): array
    {
        $gids = [];

        foreach ($collections as $collection) {
            if ($createMissing) {
                $ctx->ensureCreated($collection, 'shopify_collection_id');
            }

            if ($gid = Gid::make($collection->shopify_collection_id, 'Collection')) {
                $gids[] = $gid;
            }
        }

        return array_values(array_unique($gids));
    }

    private function categoryGid(?string $raw): ?string
    {
        $raw = trim((string) $raw);

        if ($raw === '') {
            return null;
        }

        return str_starts_with($raw, 'gid://') ? $raw : "gid://shopify/TaxonomyCategory/{$raw}";
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function productOptions(Product $model): array
    {
        return $model->options()->with('values')->get()
            ->map(fn ($option) => [
                'name' => (string) $option->name,
                'values' => $this->optionValueNames($option),
            ])
            ->filter(fn ($option) => $option['name'] !== '' && $option['values'] !== [])
            ->values()
            ->all();
    }

    /**
     * @return array<int, array<string, string>>
     */
    private function optionValueNames(Model $option): array
    {
        return $option->values
            ->map(fn ($value) => trim((string) ($value->label ?: $value->value)))
            ->filter()
            ->unique()
            ->map(fn ($name) => ['name' => $name])
            ->values()
            ->all();
    }

    /**
     * @param array<int, string> $beforeNames
     */
    private function syncOptions(Product $model, string $gid, array $beforeNames, SyncContext $ctx): void
    {
        $options = $model->options()->with('values')->get();
        $afterNames = $options->pluck('name')->filter()->values()->all();
        $added = $options->whereIn('name', array_diff($afterNames, $beforeNames));
        $removedNames = array_values(array_diff($beforeNames, $afterNames));

        foreach ($added as $option) {
            $values = $this->optionValueNames($option) ?: [['name' => 'Default']];

            $ctx->gw->mutate(<<<'GQL'
mutation ProductOptionsCreate($productId: ID!, $options: [OptionCreateInput!]!) {
  productOptionsCreate(productId: $productId, options: $options, variantStrategy: LEAVE_AS_IS) {
    product { id }
    userErrors { field message code }
  }
}
GQL, ['productId' => $gid, 'options' => [['name' => (string) $option->name, 'values' => $values]]], 'productOptionsCreate');
        }

        if ($removedNames === []) {
            return;
        }

        $shopifyOptions = $ctx->gw->query(
            'query ProductOptions($id: ID!) { product(id: $id) { options { id name } } }',
            ['id' => $gid],
        )['product']['options'] ?? [];

        $ids = collect($shopifyOptions)
            ->filter(fn ($option) => in_array((string) ($option['name'] ?? ''), $removedNames, true))
            ->pluck('id')
            ->values()
            ->all();

        if ($ids !== []) {
            $ctx->gw->mutate(<<<'GQL'
mutation ProductOptionsDelete($productId: ID!, $options: [ID!]!) {
  productOptionsDelete(productId: $productId, options: $options, strategy: NON_DESTRUCTIVE) {
    deletedOptionsIds
    userErrors { field message code }
  }
}
GQL, ['productId' => $gid, 'options' => $ids], 'productOptionsDelete');
        }
    }
}
