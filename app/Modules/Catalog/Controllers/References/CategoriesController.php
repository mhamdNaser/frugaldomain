<?php

namespace App\Modules\Catalog\Controllers\References;

use App\Http\Controllers\Controller;
use App\Modules\Catalog\Repositories\Interfaces\References\CategoriesRepositoryInterface;
use App\Modules\Catalog\Requests\References\CategoriesIndexRequest;
use App\Modules\Catalog\Requests\References\UpdateCategoryRequest;
use App\Modules\Catalog\Resources\References\CategoryTableResource;

class CategoriesController extends Controller
{
    public function __construct(
        protected CategoriesRepositoryInterface $repo
    ) {}

    public function index(CategoriesIndexRequest $request)
    {
        $data = $request->validated();
        $search = $data['search'] ?? null;
        $rowsPerPage = $data['rowsPerPage'] ?? 10;
        $page = $data['page'] ?? 1;

        $result = $this->repo->all($search, $rowsPerPage, $page);

        return response()->json([
            'data' => CategoryTableResource::collection($result->items()),
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
            'data' => new CategoryTableResource($this->repo->findForFrontend((int) $id)),
        ]);
    }

    public function update(UpdateCategoryRequest $request, $id)
    {
        return response()->json([
            'message' => 'Category updated successfully',
            'data' => new CategoryTableResource($this->repo->update((int) $id, $request->validated())),
        ]);
    }
}
