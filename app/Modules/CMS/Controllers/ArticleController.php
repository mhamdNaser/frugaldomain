<?php

namespace App\Modules\CMS\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\CMS\Controllers\Concerns\RespondsWithCmsPaginator;
use App\Modules\CMS\Repositories\Interfaces\ArticlesRepositoryInterface;
use App\Modules\CMS\Requests\ArticlesIndexRequest;
use App\Modules\CMS\Requests\ArticleShowRequest;
use App\Modules\CMS\Resources\ArticleDetailResource;
use App\Modules\CMS\Resources\ArticleTableResource;

class ArticleController extends Controller
{
    use RespondsWithCmsPaginator;

    public function __construct(protected ArticlesRepositoryInterface $repo) {}

    public function index(ArticlesIndexRequest $request)
    {
        $data = $request->validated();

        return $this->paginatedResponse(
            $this->repo->all($data['search'] ?? null, $data['rowsPerPage'] ?? 10, $data['page'] ?? 1, $this->requestFilters($data)),
            ArticleTableResource::class,
        );
    }

    public function show(ArticleShowRequest $request, int $id)
    {
        $data = $request->validated();

        return response()->json([
            'data' => new ArticleDetailResource($this->repo->findForFrontend((int) ($data['id'] ?? $id))),
        ]);
    }
}
