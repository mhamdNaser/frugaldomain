<?php

namespace App\Modules\Orders\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Orders\Repositories\Interfaces\OrderReturnsRepositoryInterface;
use App\Modules\Orders\Requests\OrderReturnsIndexRequest;
use App\Modules\Orders\Requests\UpdateOrderReturnRequest;
use App\Modules\Orders\Resources\OrderReturnTableResource;

class OrderReturnController extends Controller
{
    public function __construct(
        protected OrderReturnsRepositoryInterface $repo
    ) {}

    public function index(OrderReturnsIndexRequest $request)
    {
        $data = $request->validated();
        $result = $this->repo->all(
            $data['search'] ?? null,
            $data['rowsPerPage'] ?? 10,
            $data['page'] ?? 1,
            $data['order_id'] ?? null,
        );

        return response()->json([
            'data' => OrderReturnTableResource::collection($result->items()),
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
            'data' => new OrderReturnTableResource($this->repo->findForFrontend((int) $id)),
        ]);
    }

    public function update(UpdateOrderReturnRequest $request, $id)
    {
        return response()->json([
            'message' => 'Order return updated successfully',
            'data' => new OrderReturnTableResource($this->repo->update((int) $id, $request->validated())),
        ]);
    }
}
