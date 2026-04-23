<?php

namespace App\Modules\Catalog\Controllers\References;

use App\Http\Controllers\Controller;
use App\Modules\Catalog\Repositories\Interfaces\References\OptionsRepositoryInterface;
use App\Modules\Catalog\Requests\References\OptionsIndexRequest;
use App\Modules\Catalog\Requests\References\UpdateOptionRequest;
use App\Modules\Catalog\Resources\References\OptionTableResource;

class OptionsController extends Controller
{
    public function __construct(
        protected OptionsRepositoryInterface $repo
    ) {}

    public function index(OptionsIndexRequest $request)
    {
        $data = $request->validated();
        $search = $data['search'] ?? null;
        $rowsPerPage = $data['rowsPerPage'] ?? 10;
        $page = $data['page'] ?? 1;

        $result = $this->repo->all($search, $rowsPerPage, $page);

        return response()->json([
            'data' => OptionTableResource::collection($result->items()),
            'meta' => [
                'total' => $result->total(),
                'per_page' => $result->perPage(),
                'current_page' => $result->currentPage(),
                'last_page' => $result->lastPage(),
                'from' => $result->firstItem(),
                'to' => $result->lastItem(),
            ],
            'links' => [
                'first' => $result->url(1),
                'last' => $result->url($result->lastPage()),
                'prev' => $result->previousPageUrl(),
                'next' => $result->nextPageUrl(),
            ],
        ]);
    }

    public function show($id)
    {
        return response()->json([
            'data' => new OptionTableResource($this->repo->findForFrontend((int) $id)),
        ]);
    }

    public function update(UpdateOptionRequest $request, $id)
    {
        return response()->json([
            'message' => 'Option updated successfully',
            'data' => new OptionTableResource($this->repo->update((int) $id, $request->validated())),
        ]);
    }
}
