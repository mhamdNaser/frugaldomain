<?php

namespace App\Modules\Orders\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Orders\Repositories\Interfaces\OrderItemDutiesRepositoryInterface;
use App\Modules\Orders\Requests\OrderItemDutiesIndexRequest;
use App\Modules\Orders\Requests\UpdateOrderItemDutyRequest;
use App\Modules\Orders\Resources\OrderItemDutyTableResource;

class OrderItemDutyController extends Controller
{
    public function __construct(
        protected OrderItemDutiesRepositoryInterface $repo
    ) {}

    public function index(OrderItemDutiesIndexRequest $request)
    {
        $data = $request->validated();
        $result = $this->repo->all(
            $data['search'] ?? null,
            $data['rowsPerPage'] ?? 10,
            $data['page'] ?? 1,
            $data['order_duty_id'] ?? null,
            $data['order_item_id'] ?? null,
        );

        return response()->json([
            'data' => OrderItemDutyTableResource::collection($result->items()),
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
            'data' => new OrderItemDutyTableResource($this->repo->findForFrontend((int) $id)),
        ]);
    }

    public function update(UpdateOrderItemDutyRequest $request, $id)
    {
        return response()->json([
            'message' => 'Order item duty updated successfully',
            'data' => new OrderItemDutyTableResource($this->repo->update((int) $id, $request->validated())),
        ]);
    }
}
