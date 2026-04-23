<?php

namespace App\Modules\Orders\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Orders\Repositories\Interfaces\OrderDutiesRepositoryInterface;
use App\Modules\Orders\Requests\OrderDutiesIndexRequest;
use App\Modules\Orders\Requests\UpdateOrderDutyRequest;
use App\Modules\Orders\Resources\OrderDutyTableResource;

class OrderDutyController extends Controller
{
    public function __construct(
        protected OrderDutiesRepositoryInterface $repo
    ) {}

    public function index(OrderDutiesIndexRequest $request)
    {
        $data = $request->validated();
        $result = $this->repo->all(
            $data['search'] ?? null,
            $data['rowsPerPage'] ?? 10,
            $data['page'] ?? 1,
            $data['order_id'] ?? null,
        );

        return response()->json([
            'data' => OrderDutyTableResource::collection($result->items()),
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
            'data' => new OrderDutyTableResource($this->repo->findForFrontend((int) $id)),
        ]);
    }

    public function update(UpdateOrderDutyRequest $request, $id)
    {
        return response()->json([
            'message' => 'Order duty updated successfully',
            'data' => new OrderDutyTableResource($this->repo->update((int) $id, $request->validated())),
        ]);
    }
}
