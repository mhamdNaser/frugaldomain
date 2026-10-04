<?php

namespace App\Modules\Shopify\AutoSync\Syncers;

use App\Modules\Shopify\AutoSync\SyncContext;
use Illuminate\Database\Eloquent\Model;

/**
 * Vendors, product types and tags are not standalone resources in Shopify: they are plain
 * text attributes of products. Creating one has nothing to push until it is assigned to a
 * product; renaming or deleting one re-pushes every linked product.
 */
abstract class ProductAttributeSyncer extends BaseSyncer
{
    /** Product field key (as used by ProductSyncer) that carries this attribute. */
    abstract protected function productField(): string;

    public function snapshot(Model $model): array
    {
        return [
            'name' => $model->name,
            'product_ids' => $model->products()->pluck('products.id')->all(),
        ];
    }

    public function create(Model $model, SyncContext $ctx): void
    {
        $ctx->note(class_basename($model) . ' saved. Shopify will show it once it is assigned to a product.');
    }

    public function update(Model $model, array $before, array $changed, SyncContext $ctx): void
    {
        if (($before['name'] ?? null) === $model->name) {
            return;
        }

        $ctx->propagateToProducts($before['product_ids'] ?? [], [$this->productField()]);
    }

    public function delete(array $snapshot, SyncContext $ctx): void
    {
        $ctx->propagateToProducts($snapshot['product_ids'] ?? [], [$this->productField()]);
    }
}
