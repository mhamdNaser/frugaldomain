<?php

namespace App\Modules\Orders\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Orders\Repositories\Interfaces\OrderReturnItemsRepositoryInterface;
use App\Modules\Orders\Requests\OrderReturnItemsIndexRequest;
use App\Modules\Orders\Requests\UpdateOrderReturnItemRequest;
use App\Modules\Orders\Resources\OrderReturnItemTableResource;

class OrderReturnItemController extends Controller
{
    public function __construct(
        protected OrderReturnItemsRepositoryInterface $repo
    ) {}

    public function index(OrderReturnItemsIndexRequest $request)
    {
        $data = $request->validated();
        $result = $this->repo->all(
            $data['search'] ?? null,
            $data['rowsPerPage'] ?? 10,
            $data['page'] ?? 1,
            $data['order_return_id'] ?? null,
            $data['order_item_id'] ?? null,
        );

        return response()->json([
            'data' => OrderReturnItemTableResource::collection($result->items()),
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
            'data' => new OrderReturnItemTableResource($this->repo->findForFrontend((int) $id)),
        ]);
    }

    public function update(UpdateOrderReturnItemRequest $request, $id)
    {
        return response()->json([
            'message' => 'Order return item updated successfully',
            'data' => new OrderReturnItemTableResource($this->repo->update((int) $id, $request->validated())),
        ]);
    }
}
