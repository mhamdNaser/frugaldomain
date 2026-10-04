<?php

namespace App\Modules\Shopify\AutoSync\Syncers;

use App\Modules\CMS\Models\Page;
use App\Modules\Shopify\AutoSync\Support\Gid;
use App\Modules\Shopify\AutoSync\SyncContext;
use Illuminate\Database\Eloquent\Model;

class PageSyncer extends BaseSyncer
{
    /**
     * @param Page $model
     */
    public function create(Model $model, SyncContext $ctx): void
    {
        if (Gid::make($model->shopify_page_id, 'Page')) {
            $this->update($model, [], ['title', 'handle', 'body', 'is_published', 'published_at', 'template_suffix', 'seo_title', 'seo_description'], $ctx);
            return;
        }

        $result = $ctx->gw->mutate(<<<'GQL'
mutation PageCreate($page: PageCreateInput!) {
  pageCreate(page: $page) {
    page { id handle }
    userErrors { field message }
  }
}
GQL, ['page' => $this->input($model, null)], 'pageCreate');

        $this->persist($model, [
            'shopify_page_id' => $result['page']['id'] ?? null,
            'handle' => blank($model->handle) ? ($result['page']['handle'] ?? null) : null,
        ]);
    }

    /**
     * @param Page $model
     */
    public function update(Model $model, array $before, array $changed, SyncContext $ctx): void
    {
        $gid = Gid::make($model->shopify_page_id, 'Page');

        if (!$gid) {
            $this->create($model, $ctx);
            return;
        }

        $input = $this->input($model, $changed);

        if ($input === []) {
            return;
        }

        $ctx->gw->mutate(<<<'GQL'
mutation PageUpdate($id: ID!, $page: PageUpdateInput!) {
  pageUpdate(id: $id, page: $page) {
    page { id }
    userErrors { field message }
  }
}
GQL, ['id' => $gid, 'page' => $input], 'pageUpdate');
    }

    public function delete(array $snapshot, SyncContext $ctx): void
    {
        $gid = Gid::make($snapshot['shopify_page_id'] ?? null, 'Page');

        if ($gid) {
            $this->ignoreNotFound(fn () => $ctx->gw->mutate(
                'mutation PageDelete($id: ID!) { pageDelete(id: $id) { deletedPageId userErrors { field message } } }',
                ['id' => $gid],
                'pageDelete',
            ));
        }
    }

    /**
     * @param array<int, string>|null $changed
     * @return array<string, mixed>
     */
    private function input(Page $model, ?array $changed): array
    {
        $input = [];

        if ($this->touched($changed, 'title')) {
            $input['title'] = (string) ($model->title ?: $model->handle ?: 'Page');
        }
        if ($this->touched($changed, 'handle') && filled($model->handle)) {
            $input['handle'] = (string) $model->handle;
        }
        if ($this->touched($changed, 'body')) {
            $input['body'] = (string) ($model->body ?? '');
        }
        if ($this->touched($changed, 'is_published')) {
            $input['isPublished'] = (bool) $model->is_published;
        }
        if ($this->touched($changed, 'published_at') && filled($model->published_at)) {
            $input['publishDate'] = $this->isoDate($model->published_at);
        }
        if ($this->touched($changed, 'template_suffix')) {
            $input['templateSuffix'] = $model->template_suffix;
        }
        if ($this->touched($changed, 'seo_title', 'seo_description')
            && ($metafields = $this->seoMetafields($model->seo_title, $model->seo_description)) !== []) {
            $input['metafields'] = $metafields;
        }

        return $input;
    }
}
