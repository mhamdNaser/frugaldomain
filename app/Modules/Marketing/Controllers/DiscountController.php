<?php

namespace App\Modules\Marketing\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Marketing\Repositories\Interfaces\DiscountsRepositoryInterface;
use App\Modules\Marketing\Requests\DiscountsIndexRequest;
use App\Modules\Marketing\Requests\UpdateDiscountRequest;
use App\Modules\Marketing\Resources\DiscountTableResource;

class DiscountController extends Controller
{
    public function __construct(
        protected DiscountsRepositoryInterface $repo
    ) {}

    public function index(DiscountsIndexRequest $request)
    {
        $data = $request->validated();
        $result = $this->repo->all(
            $data['search'] ?? null,
            $data['rowsPerPage'] ?? 10,
            $data['page'] ?? 1,
        );

        return response()->json([
            'data' => DiscountTableResource::collection($result->items()),
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
            'data' => new DiscountTableResource($this->repo->findForFrontend((int) $id)),
        ]);
    }

    public function update(UpdateDiscountRequest $request, $id)
    {
        return response()->json([
            'message' => 'Discount updated successfully',
            'data' => new DiscountTableResource($this->repo->update((int) $id, $request->validated())),
        ]);
    }
}
