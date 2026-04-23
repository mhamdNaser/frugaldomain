<?php

namespace App\Modules\Catalog\Controllers\References;

use App\Http\Controllers\Controller;
use App\Modules\Catalog\Repositories\Interfaces\References\VendorsRepositoryInterface;
use App\Modules\Catalog\Requests\References\UpdateVendorRequest;
use App\Modules\Catalog\Requests\References\VendorsIndexRequest;
use App\Modules\Catalog\Resources\References\VendorTableResource;

class VendorsController extends Controller
{
    public function __construct(
        protected VendorsRepositoryInterface $repo
    ) {}

    public function index(VendorsIndexRequest $request)
    {
        $data = $request->validated();
        $search = $data['search'] ?? null;
        $rowsPerPage = $data['rowsPerPage'] ?? 10;
        $page = $data['page'] ?? 1;

        $result = $this->repo->all($search, $rowsPerPage, $page);

        return response()->json([
            'data' => VendorTableResource::collection($result->items()),
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
            'data' => new VendorTableResource($this->repo->findForFrontend((int) $id)),
        ]);
    }

    public function update(UpdateVendorRequest $request, $id)
    {
        return response()->json([
            'message' => 'Vendor updated successfully',
            'data' => new VendorTableResource($this->repo->update((int) $id, $request->validated())),
        ]);
    }

    public function changeStatus($id)
    {
        $vendor = $this->repo->toggleStatus((int) $id);

        return response()->json([
            'success' => true,
            'message' => 'Status changed successfully',
            'data' => new VendorTableResource($vendor),
        ]);
    }
}
