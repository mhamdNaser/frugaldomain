<?php

namespace App\Modules\CMS\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\CMS\Controllers\Concerns\RespondsWithCmsPaginator;
use App\Modules\CMS\Repositories\Interfaces\MetaDefinitionsRepositoryInterface;
use App\Modules\CMS\Requests\MetaDefinitionsRequest;
use App\Modules\CMS\Resources\MetaDefinitionsResource;

class MetaDefinitionsController extends Controller
{
    use RespondsWithCmsPaginator;

    public function __construct(protected MetaDefinitionsRepositoryInterface $repo) {}

    public function index(MetaDefinitionsRequest $request)
    {
        $data = $request->validated();

        return $this->paginatedResponse(
            $this->repo->all($data['search'] ?? null, $data['rowsPerPage'] ?? 10, $data['page'] ?? 1, $this->requestFilters($data)),
            MetaDefinitionsResource::class,
        );
    }
}
