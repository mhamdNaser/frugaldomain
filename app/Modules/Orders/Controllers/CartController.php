<?php

namespace App\Modules\Orders\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Orders\Repositories\Interfaces\CartsRepositoryInterface;
use App\Modules\Orders\Requests\CartsIndexRequest;
use App\Modules\Orders\Requests\UpdateCartRequest;
use App\Modules\Orders\Resources\CartTableResource;

class CartController extends Controller
{
    public function __construct(
        protected CartsRepositoryInterface $repo
    ) {}

    public function index(CartsIndexRequest $request)
    {
        $data = $request->validated();
        $result = $this->repo->all(
            $data['search'] ?? null,
            $data['rowsPerPage'] ?? 10,
            $data['page'] ?? 1,
            $data['customer_id'] ?? null,
        );

        return response()->json([
            'data' => CartTableResource::collection($result->items()),
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
            'data' => new CartTableResource($this->repo->findForFrontend((int) $id)),
        ]);
    }

    public function update(UpdateCartRequest $request, $id)
    {
        return response()->json([
            'message' => 'Cart updated successfully',
            'data' => new CartTableResource($this->repo->update((int) $id, $request->validated())),
        ]);
    }
}
