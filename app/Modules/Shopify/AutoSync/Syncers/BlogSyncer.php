<?php

namespace App\Modules\Shopify\AutoSync\Syncers;

use App\Modules\CMS\Models\Blog;
use App\Modules\Shopify\AutoSync\Support\Gid;
use App\Modules\Shopify\AutoSync\SyncContext;
use Illuminate\Database\Eloquent\Model;

class BlogSyncer extends BaseSyncer
{
    /**
     * @param Blog $model
     */
    public function create(Model $model, SyncContext $ctx): void
    {
        if (Gid::make($model->shopify_blog_id, 'Blog')) {
            $this->update($model, [], ['title', 'handle', 'comment_policy', 'template_suffix', 'seo_title', 'seo_description'], $ctx);
            return;
        }

        $result = $ctx->gw->mutate(<<<'GQL'
mutation BlogCreate($blog: BlogCreateInput!) {
  blogCreate(blog: $blog) {
    blog { id handle }
    userErrors { field message }
  }
}
GQL, ['blog' => $this->input($model, null)], 'blogCreate');

        $this->persist($model, [
            'shopify_blog_id' => $result['blog']['id'] ?? null,
            'handle' => blank($model->handle) ? ($result['blog']['handle'] ?? null) : null,
        ]);
    }

    /**
     * @param Blog $model
     */
    public function update(Model $model, array $before, array $changed, SyncContext $ctx): void
    {
        $gid = Gid::make($model->shopify_blog_id, 'Blog');

        if (!$gid) {
            $this->create($model, $ctx);
            return;
        }

        $input = $this->input($model, $changed);

        if ($input === []) {
            return;
        }

        $ctx->gw->mutate(<<<'GQL'
mutation BlogUpdate($id: ID!, $blog: BlogUpdateInput!) {
  blogUpdate(id: $id, blog: $blog) {
    blog { id }
    userErrors { field message }
  }
}
GQL, ['id' => $gid, 'blog' => $input], 'blogUpdate');
    }

    public function delete(array $snapshot, SyncContext $ctx): void
    {
        $gid = Gid::make($snapshot['shopify_blog_id'] ?? null, 'Blog');

        if ($gid) {
            $this->ignoreNotFound(fn () => $ctx->gw->mutate(
                'mutation BlogDelete($id: ID!) { blogDelete(id: $id) { deletedBlogId userErrors { field message } } }',
                ['id' => $gid],
                'blogDelete',
            ));
        }
    }

    /**
     * @param array<int, string>|null $changed
     * @return array<string, mixed>
     */
    private function input(Blog $model, ?array $changed): array
    {
        $input = [];

        if ($this->touched($changed, 'title')) {
            $input['title'] = (string) ($model->title ?: $model->handle ?: 'Blog');
        }
        if ($this->touched($changed, 'handle') && filled($model->handle)) {
            $input['handle'] = (string) $model->handle;
        }
        if ($this->touched($changed, 'template_suffix')) {
            $input['templateSuffix'] = $model->template_suffix;
        }
        if ($this->touched($changed, 'comment_policy')) {
            $policy = strtoupper(str_replace([' ', '-'], '_', (string) $model->comment_policy));
            if (in_array($policy, ['AUTO_PUBLISHED', 'CLOSED', 'MODERATED'], true)) {
                $input['commentPolicy'] = $policy;
            }
        }
        if ($this->touched($changed, 'seo_title', 'seo_description')
            && ($metafields = $this->seoMetafields($model->seo_title, $model->seo_description)) !== []) {
            $input['metafields'] = $metafields;
        }

        return $input;
    }
}
