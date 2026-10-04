<?php

namespace App\Modules\Shopify\AutoSync\Syncers;

use App\Modules\CMS\Models\Article;
use App\Modules\CMS\Models\Blog;
use App\Modules\Shopify\AutoSync\Support\Gid;
use App\Modules\Shopify\AutoSync\SyncContext;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class ArticleSyncer extends BaseSyncer
{
    private const ALL_FIELDS = ['blog_id', 'title', 'handle', 'body', 'summary', 'author_name', 'tags', 'is_published', 'published_at', 'template_suffix', 'seo_title', 'seo_description'];

    /**
     * @param Article $model
     */
    public function create(Model $model, SyncContext $ctx): void
    {
        if (Gid::make($model->shopify_article_id, 'Article')) {
            $this->update($model, [], self::ALL_FIELDS, $ctx);
            return;
        }

        $input = $this->input($model, null, $ctx);
        $input['author'] = ['name' => (string) ($model->author_name ?: $ctx->gw->store->name ?: 'Admin')];

        $result = $ctx->gw->mutate(<<<'GQL'
mutation ArticleCreate($article: ArticleCreateInput!) {
  articleCreate(article: $article) {
    article { id handle }
    userErrors { field message }
  }
}
GQL, ['article' => $input], 'articleCreate');

        $this->persist($model, [
            'shopify_article_id' => $result['article']['id'] ?? null,
            'handle' => blank($model->handle) ? ($result['article']['handle'] ?? null) : null,
        ]);
    }

    /**
     * @param Article $model
     */
    public function update(Model $model, array $before, array $changed, SyncContext $ctx): void
    {
        $gid = Gid::make($model->shopify_article_id, 'Article');

        if (!$gid) {
            $this->create($model, $ctx);
            return;
        }

        $input = $this->input($model, $changed, $ctx);

        if ($this->touched($changed, 'author_name') && filled($model->author_name)) {
            $input['author'] = ['name' => (string) $model->author_name];
        }

        if ($input === []) {
            return;
        }

        $ctx->gw->mutate(<<<'GQL'
mutation ArticleUpdate($id: ID!, $article: ArticleUpdateInput!) {
  articleUpdate(id: $id, article: $article) {
    article { id }
    userErrors { field message }
  }
}
GQL, ['id' => $gid, 'article' => $input], 'articleUpdate');
    }

    public function delete(array $snapshot, SyncContext $ctx): void
    {
        $gid = Gid::make($snapshot['shopify_article_id'] ?? null, 'Article');

        if ($gid) {
            $this->ignoreNotFound(fn () => $ctx->gw->mutate(
                'mutation ArticleDelete($id: ID!) { articleDelete(id: $id) { deletedArticleId userErrors { field message } } }',
                ['id' => $gid],
                'articleDelete',
            ));
        }
    }

    /**
     * @param array<int, string>|null $changed
     * @return array<string, mixed>
     */
    private function input(Article $model, ?array $changed, SyncContext $ctx): array
    {
        $input = [];

        if ($this->touched($changed, 'blog_id')) {
            $blog = Blog::query()->find($model->blog_id);

            if (!$blog) {
                throw ValidationException::withMessages(['blog_id' => 'A blog is required to publish the article to Shopify.']);
            }

            $input['blogId'] = Gid::make($ctx->ensureCreated($blog, 'shopify_blog_id')->shopify_blog_id, 'Blog');
        }
        if ($this->touched($changed, 'title')) {
            $input['title'] = (string) ($model->title ?: $model->handle ?: 'Article');
        }
        if ($this->touched($changed, 'handle') && filled($model->handle)) {
            $input['handle'] = (string) $model->handle;
        }
        if ($this->touched($changed, 'body')) {
            $input['body'] = (string) ($model->body ?? '');
        }
        if ($this->touched($changed, 'summary')) {
            $input['summary'] = $model->summary;
        }
        if ($this->touched($changed, 'tags')) {
            $input['tags'] = $this->tagList($model->tags);
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
