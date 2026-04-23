<?php

namespace App\Modules\CMS\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\CMS\Controllers\Concerns\RespondsWithCmsPaginator;
use App\Modules\CMS\Repositories\Interfaces\PagesRepositoryInterface;
use App\Modules\CMS\Requests\PagesIndexRequest;
use App\Modules\CMS\Resources\PageTableResource;

class PageController extends Controller
{
    use RespondsWithCmsPaginator;

    public function __construct(protected PagesRepositoryInterface $repo) {}

    public function index(PagesIndexRequest $request)
    {
        $data = $request->validated();

        return $this->paginatedResponse(
            $this->repo->all($data['search'] ?? null, $data['rowsPerPage'] ?? 10, $data['page'] ?? 1, $this->requestFilters($data)),
            PageTableResource::class,
        );
    }
}
