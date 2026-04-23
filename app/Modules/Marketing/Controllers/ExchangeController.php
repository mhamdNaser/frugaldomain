<?php

namespace App\Modules\Marketing\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Marketing\Repositories\Interfaces\ExchangesRepositoryInterface;
use App\Modules\Marketing\Requests\ExchangesIndexRequest;
use App\Modules\Marketing\Resources\ExchangeTableResource;

class ExchangeController extends Controller
{
    public function __construct(
        protected ExchangesRepositoryInterface $repo
    ) {}

    public function index(ExchangesIndexRequest $request)
    {
        $data = $request->validated();
        $result = $this->repo->all(
            $data['search'] ?? null,
            (int) ($data['rowsPerPage'] ?? 10),
            (int) ($data['page'] ?? 1),
            $data['order_return_id'] ?? null,
        );

        return response()->json([
            'data' => ExchangeTableResource::collection($result->items()),
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
}

