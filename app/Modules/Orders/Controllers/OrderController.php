<?php

namespace App\Modules\Orders\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Orders\Repositories\Interfaces\OrdersRepositoryInterface;
use App\Modules\Orders\Requests\OrdersIndexRequest;
use App\Modules\Orders\Resources\OrderDetailResource;
use App\Modules\Orders\Requests\UpdateOrderRequest;
use App\Modules\Orders\Resources\OrderTableResource;

class OrderController extends Controller
{
    public function __construct(
        protected OrdersRepositoryInterface $repo
    ) {}

    public function index(OrdersIndexRequest $request)
    {
        $data = $request->validated();
        $result = $this->repo->all(
            $data['search'] ?? null,
            $data['rowsPerPage'] ?? 10,
            $data['page'] ?? 1,
            $data['customer_id'] ?? null,
        );

        return response()->json([
            'data' => OrderTableResource::collection($result->items()),
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
            'data' => new OrderDetailResource($this->repo->findForFrontend((int) $id)),
        ]);
    }

    public function update(UpdateOrderRequest $request, $id)
    {
        return response()->json([
            'message' => 'Order updated successfully',
            'data' => new OrderTableResource($this->repo->update((int) $id, $request->validated())),
        ]);
    }
}
