<?php

namespace App\Modules\CMS\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\CMS\Controllers\Concerns\RespondsWithCmsPaginator;
use App\Modules\CMS\Repositories\Interfaces\MetaobjectsRepositoryInterface;
use App\Modules\CMS\Requests\MetaobjectsIndexRequest;
use App\Modules\CMS\Resources\MetaobjectTableResource;

class MetaobjectController extends Controller
{
    use RespondsWithCmsPaginator;

    public function __construct(protected MetaobjectsRepositoryInterface $repo) {}

    public function index(MetaobjectsIndexRequest $request)
    {
        $data = $request->validated();

        return $this->paginatedResponse(
            $this->repo->all($data['search'] ?? null, $data['rowsPerPage'] ?? 10, $data['page'] ?? 1, $this->requestFilters($data)),
            MetaobjectTableResource::class,
        );
    }
}
