<?php

namespace App\Modules\Marketing\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Marketing\Repositories\Interfaces\DiscountCodesRepositoryInterface;
use App\Modules\Marketing\Requests\DiscountCodesIndexRequest;
use App\Modules\Marketing\Requests\UpdateDiscountCodeRequest;
use App\Modules\Marketing\Resources\DiscountCodeTableResource;

class DiscountCodeController extends Controller
{
    public function __construct(
        protected DiscountCodesRepositoryInterface $repo
    ) {}

    public function index(DiscountCodesIndexRequest $request)
    {
        $data = $request->validated();
        $result = $this->repo->all(
            $data['search'] ?? null,
            $data['rowsPerPage'] ?? 10,
            $data['page'] ?? 1,
            $data['discount_id'] ?? null,
        );

        return response()->json([
            'data' => DiscountCodeTableResource::collection($result->items()),
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
            'data' => new DiscountCodeTableResource($this->repo->findForFrontend((int) $id)),
        ]);
    }

    public function update(UpdateDiscountCodeRequest $request, $id)
    {
        return response()->json([
            'message' => 'Discount code updated successfully',
            'data' => new DiscountCodeTableResource($this->repo->update((int) $id, $request->validated())),
        ]);
    }
}
