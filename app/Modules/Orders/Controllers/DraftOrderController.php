<?php

namespace App\Modules\Orders\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Orders\Repositories\Interfaces\DraftOrdersRepositoryInterface;
use App\Modules\Orders\Requests\DraftOrdersIndexRequest;
use App\Modules\Orders\Resources\DraftOrderDetailResource;
use App\Modules\Orders\Requests\UpdateDraftOrderRequest;
use App\Modules\Orders\Resources\DraftOrderTableResource;

class DraftOrderController extends Controller
{
    public function __construct(
        protected DraftOrdersRepositoryInterface $repo
    ) {}

    public function index(DraftOrdersIndexRequest $request)
    {
        $data = $request->validated();
        $result = $this->repo->all(
            $data['search'] ?? null,
            $data['rowsPerPage'] ?? 10,
            $data['page'] ?? 1,
            $data['customer_id'] ?? null,
        );

        return response()->json([
            'data' => DraftOrderTableResource::collection($result->items()),
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
            'data' => new DraftOrderDetailResource($this->repo->findForFrontend((int) $id)),
        ]);
    }

    public function update(UpdateDraftOrderRequest $request, $id)
    {
        return response()->json([
            'message' => 'Draft order updated successfully',
            'data' => new DraftOrderTableResource($this->repo->update((int) $id, $request->validated())),
        ]);
    }
}
