<?php

namespace App\Modules\Catalog\Controllers\References;

use App\Http\Controllers\Controller;
use App\Modules\Catalog\Repositories\Interfaces\References\CollectionsRepositoryInterface;
use App\Modules\Catalog\Requests\References\CollectionsIndexRequest;
use App\Modules\Catalog\Requests\References\UpdateCollectionRequest;
use App\Modules\Catalog\Resources\References\CollectionTableResource;

class CollectionsController extends Controller
{
    public function __construct(
        protected CollectionsRepositoryInterface $repo
    ) {}

    public function index(CollectionsIndexRequest $request)
    {
        $data = $request->validated();
        $search = $data['search'] ?? null;
        $rowsPerPage = $data['rowsPerPage'] ?? 10;
        $page = $data['page'] ?? 1;

        $result = $this->repo->all($search, $rowsPerPage, $page);

        return response()->json([
            'data' => CollectionTableResource::collection($result->items()),
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
            'data' => new CollectionTableResource($this->repo->findForFrontend((int) $id)),
        ]);
    }

    public function update(UpdateCollectionRequest $request, $id)
    {
        return response()->json([
            'message' => 'Collection updated successfully',
            'data' => new CollectionTableResource($this->repo->update((int) $id, $request->validated())),
        ]);
    }

    public function changeStatus($id)
    {
        $collection = $this->repo->toggleStatus((int) $id);

        return response()->json([
            'success' => true,
            'message' => 'Status changed successfully',
            'data' => new CollectionTableResource($collection),
        ]);
    }
}
