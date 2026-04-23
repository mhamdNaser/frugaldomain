<?php

namespace App\Modules\CMS\Repositories\Eloquent;

use App\Modules\CMS\Models\Article;
use App\Modules\CMS\Repositories\Eloquent\Concerns\PaginatesCmsTables;
use App\Modules\CMS\Repositories\Interfaces\ArticlesRepositoryInterface;

class FrontendArticlesRepository implements ArticlesRepositoryInterface
{
    use PaginatesCmsTables;

    public function __construct(protected Article $model) {}

    public function all(?string $search = null, int $rowsPerPage = 10, int $page = 1, array $filters = [])
    {
        return $this->paginateQuery(
            $this->model->newQuery()->with('blog:id,title'),
            $search,
            $rowsPerPage,
            $page,
            $filters,
            ['title', 'handle', 'author_name', 'seo_title', 'seo_description'],
            ['blog_id'],
            'published_at',
        );
    }

    public function findForFrontend(int $id)
    {
        return $this->applyTenantScope($this->model
            ->newQuery()
            ->with([
                'blog:id,title,handle,shopify_blog_id',
                'comments' => fn ($query) => $query
                    ->orderByDesc('published_at')
                    ->orderByDesc('id'),
            ]))
            ->findOrFail($id);
    }

    private function applyTenantScope($query)
    {
        $user = auth()->user();

        if (
            $user
            && method_exists($user, 'hasRole')
            && $user->hasRole('partner')
            && !$user->hasRole('admin')
        ) {
            $storeId = $user->store?->id;
            abort_if(!$storeId, 404, 'No store is linked to the authenticated user.');
            $query->where('store_id', $storeId);
        }

        return $query;
    }
}
