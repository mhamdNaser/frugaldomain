<?php

namespace App\Modules\Orders\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Orders\Repositories\Interfaces\OrderItemsRepositoryInterface;
use App\Modules\Orders\Requests\OrderItemsIndexRequest;
use App\Modules\Orders\Requests\UpdateOrderItemRequest;
use App\Modules\Orders\Resources\OrderItemTableResource;

class OrderItemController extends Controller
{
    public function __construct(
        protected OrderItemsRepositoryInterface $repo
    ) {}

    public function index(OrderItemsIndexRequest $request)
    {
        $data = $request->validated();
        $result = $this->repo->all(
            $data['search'] ?? null,
            $data['rowsPerPage'] ?? 10,
            $data['page'] ?? 1,
            $data['order_id'] ?? null,
        );

        return response()->json([
            'data' => OrderItemTableResource::collection($result->items()),
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
            'data' => new OrderItemTableResource($this->repo->findForFrontend((int) $id)),
        ]);
    }

    public function update(UpdateOrderItemRequest $request, $id)
    {
        return response()->json([
            'message' => 'Order item updated successfully',
            'data' => new OrderItemTableResource($this->repo->update((int) $id, $request->validated())),
        ]);
    }
}
