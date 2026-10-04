<?php

namespace App\Modules\Shopify\AutoSync\Syncers;

use App\Modules\Catalog\Models\Collection;
use App\Modules\Shopify\AutoSync\Support\Gid;
use App\Modules\Shopify\AutoSync\SyncContext;
use Illuminate\Database\Eloquent\Model;

class CollectionSyncer extends BaseSyncer
{
    /**
     * @param Collection $model
     */
    public function create(Model $model, SyncContext $ctx): void
    {
        if (Gid::make($model->shopify_collection_id, 'Collection')) {
            $this->update($model, [], ['title', 'handle', 'description', 'image_url', 'seo_title', 'seo_description', 'is_active'], $ctx);
            return;
        }

        $result = $ctx->gw->mutate(<<<'GQL'
mutation CollectionCreate($input: CollectionInput!) {
  collectionCreate(input: $input) {
    collection { id handle }
    userErrors { field message }
  }
}
GQL, ['input' => $this->input($model, null)], 'collectionCreate');

        $gid = (string) ($result['collection']['id'] ?? '');

        $this->persist($model, [
            'shopify_collection_id' => $gid,
            'handle' => blank($model->handle) ? ($result['collection']['handle'] ?? null) : null,
        ]);

        if ($gid !== '' && $model->is_active !== false) {
            $ctx->gw->publish($gid);
        }
    }

    /**
     * @param Collection $model
     */
    public function update(Model $model, array $before, array $changed, SyncContext $ctx): void
    {
        $gid = Gid::make($model->shopify_collection_id, 'Collection');

        if (!$gid) {
            $this->create($model, $ctx);
            return;
        }

        $input = $this->input($model, $changed);

        if (count($input) > 0) {
            $ctx->gw->mutate(<<<'GQL'
mutation CollectionUpdate($input: CollectionInput!) {
  collectionUpdate(input: $input) {
    collection { id }
    userErrors { field message }
  }
}
GQL, ['input' => ['id' => $gid] + $input], 'collectionUpdate');
        }

        if (in_array('is_active', $changed, true)) {
            $model->is_active ? $ctx->gw->publish($gid) : $ctx->gw->unpublish($gid);
        }
    }

    public function delete(array $snapshot, SyncContext $ctx): void
    {
        $gid = Gid::make($snapshot['shopify_collection_id'] ?? null, 'Collection');

        if (!$gid) {
            return;
        }

        $this->ignoreNotFound(fn () => $ctx->gw->mutate(<<<'GQL'
mutation CollectionDelete($input: CollectionDeleteInput!) {
  collectionDelete(input: $input) {
    deletedCollectionId
    userErrors { field message }
  }
}
GQL, ['input' => ['id' => $gid]], 'collectionDelete'));
    }

    /**
     * @param array<int, string>|null $changed
     * @return array<string, mixed>
     */
    private function input(Collection $model, ?array $changed): array
    {
        $input = [];

        if ($this->touched($changed, 'title')) {
            $input['title'] = (string) $model->title;
        }
        if ($this->touched($changed, 'handle') && filled($model->handle)) {
            $input['handle'] = (string) $model->handle;
        }
        if ($this->touched($changed, 'description')) {
            $input['descriptionHtml'] = (string) ($model->description ?? '');
        }
        if ($this->touched($changed, 'seo_title', 'seo_description')) {
            $input['seo'] = ['title' => $model->seo_title, 'description' => $model->seo_description];
        }
        if ($this->touched($changed, 'image_url', 'image_alt') && $this->isPublicUrl($model->image_url)) {
            $input['image'] = ['src' => $model->image_url, 'altText' => $model->image_alt ?: $model->title];
        }

        return $input;
    }
}
