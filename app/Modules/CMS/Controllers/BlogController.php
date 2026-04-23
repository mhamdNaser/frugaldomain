<?php

namespace App\Modules\CMS\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\CMS\Controllers\Concerns\RespondsWithCmsPaginator;
use App\Modules\CMS\Repositories\Interfaces\BlogsRepositoryInterface;
use App\Modules\CMS\Requests\BlogsIndexRequest;
use App\Modules\CMS\Resources\BlogTableResource;

class BlogController extends Controller
{
    use RespondsWithCmsPaginator;

    public function __construct(protected BlogsRepositoryInterface $repo) {}

    public function index(BlogsIndexRequest $request)
    {
        $data = $request->validated();

        return $this->paginatedResponse(
            $this->repo->all($data['search'] ?? null, $data['rowsPerPage'] ?? 10, $data['page'] ?? 1, $this->requestFilters($data)),
            BlogTableResource::class,
        );
    }
}
