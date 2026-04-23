<?php

namespace App\Modules\Catalog\Controllers\References;

use App\Http\Controllers\Controller;
use App\Modules\Catalog\Repositories\Interfaces\References\TagsRepositoryInterface;
use App\Modules\Catalog\Requests\References\TagsIndexRequest;
use App\Modules\Catalog\Requests\References\UpdateTagRequest;
use App\Modules\Catalog\Resources\References\TagTableResource;

class TagsController extends Controller
{
    public function __construct(
        protected TagsRepositoryInterface $repo
    ) {}

    public function index(TagsIndexRequest $request)
    {
        $data = $request->validated();
        $search = $data['search'] ?? null;
        $rowsPerPage = $data['rowsPerPage'] ?? 10;
        $page = $data['page'] ?? 1;

        $result = $this->repo->all($search, $rowsPerPage, $page);

        return response()->json([
            'data' => TagTableResource::collection($result->items()),
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
            'data' => new TagTableResource($this->repo->findForFrontend((int) $id)),
        ]);
    }

    public function update(UpdateTagRequest $request, $id)
    {
        return response()->json([
            'message' => 'Tag updated successfully',
            'data' => new TagTableResource($this->repo->update((int) $id, $request->validated())),
        ]);
    }
}
