<?php

namespace App\Modules\Billing\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Billing\Repositories\Interfaces\RefundItemsRepositoryInterface;
use App\Modules\Billing\Requests\RefundItemsIndexRequest;
use App\Modules\Billing\Requests\UpdateRefundItemRequest;
use App\Modules\Billing\Resources\RefundItemTableResource;

class RefundItemController extends Controller
{
    public function __construct(
        protected RefundItemsRepositoryInterface $repo
    ) {}

    public function index(RefundItemsIndexRequest $request)
    {
        $data = $request->validated();
        $result = $this->repo->all(
            $data['search'] ?? null,
            $data['rowsPerPage'] ?? 10,
            $data['page'] ?? 1,
            $data['refund_id'] ?? null,
        );

        return response()->json([
            'data' => RefundItemTableResource::collection($result->items()),
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
            'data' => new RefundItemTableResource($this->repo->findForFrontend((int) $id)),
        ]);
    }

    public function update(UpdateRefundItemRequest $request, $id)
    {
        return response()->json([
            'message' => 'Refund item updated successfully',
            'data' => new RefundItemTableResource($this->repo->update((int) $id, $request->validated())),
        ]);
    }
}
