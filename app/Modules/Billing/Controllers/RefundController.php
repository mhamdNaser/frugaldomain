<?php

namespace App\Modules\Billing\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Billing\Repositories\Interfaces\RefundsRepositoryInterface;
use App\Modules\Billing\Requests\RefundsIndexRequest;
use App\Modules\Billing\Requests\UpdateRefundRequest;
use App\Modules\Billing\Resources\RefundTableResource;

class RefundController extends Controller
{
    public function __construct(
        protected RefundsRepositoryInterface $repo
    ) {}

    public function index(RefundsIndexRequest $request)
    {
        $data = $request->validated();
        $result = $this->repo->all(
            $data['search'] ?? null,
            $data['rowsPerPage'] ?? 10,
            $data['page'] ?? 1,
            $data['order_id'] ?? null,
        );

        return response()->json([
            'data' => RefundTableResource::collection($result->items()),
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
            'data' => new RefundTableResource($this->repo->findForFrontend((int) $id)),
        ]);
    }

    public function update(UpdateRefundRequest $request, $id)
    {
        return response()->json([
            'message' => 'Refund updated successfully',
            'data' => new RefundTableResource($this->repo->update((int) $id, $request->validated())),
        ]);
    }
}
