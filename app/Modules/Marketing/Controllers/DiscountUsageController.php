<?php

namespace App\Modules\Marketing\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Marketing\Repositories\Interfaces\DiscountUsagesRepositoryInterface;
use App\Modules\Marketing\Requests\DiscountUsagesIndexRequest;
use App\Modules\Marketing\Requests\UpdateDiscountUsageRequest;
use App\Modules\Marketing\Resources\DiscountUsageTableResource;

class DiscountUsageController extends Controller
{
    public function __construct(
        protected DiscountUsagesRepositoryInterface $repo
    ) {}

    public function index(DiscountUsagesIndexRequest $request)
    {
        $data = $request->validated();
        $result = $this->repo->all(
            $data['search'] ?? null,
            $data['rowsPerPage'] ?? 10,
            $data['page'] ?? 1,
            $data['discount_id'] ?? null,
            $data['order_id'] ?? null,
        );

        return response()->json([
            'data' => DiscountUsageTableResource::collection($result->items()),
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
            'data' => new DiscountUsageTableResource($this->repo->findForFrontend((int) $id)),
        ]);
    }

    public function update(UpdateDiscountUsageRequest $request, $id)
    {
        return response()->json([
            'message' => 'Discount usage updated successfully',
            'data' => new DiscountUsageTableResource($this->repo->update((int) $id, $request->validated())),
        ]);
    }
}
