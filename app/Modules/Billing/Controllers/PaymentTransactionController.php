<?php

namespace App\Modules\Billing\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Billing\Repositories\Interfaces\PaymentTransactionsRepositoryInterface;
use App\Modules\Billing\Requests\PaymentTransactionsIndexRequest;
use App\Modules\Billing\Requests\UpdatePaymentTransactionRequest;
use App\Modules\Billing\Resources\PaymentTransactionTableResource;

class PaymentTransactionController extends Controller
{
    public function __construct(
        protected PaymentTransactionsRepositoryInterface $repo
    ) {}

    public function index(PaymentTransactionsIndexRequest $request)
    {
        $data = $request->validated();
        $result = $this->repo->all(
            $data['search'] ?? null,
            $data['rowsPerPage'] ?? 10,
            $data['page'] ?? 1,
            $data['order_id'] ?? null,
        );

        return response()->json([
            'data' => PaymentTransactionTableResource::collection($result->items()),
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
            'data' => new PaymentTransactionTableResource($this->repo->findForFrontend((int) $id)),
        ]);
    }

    public function update(UpdatePaymentTransactionRequest $request, $id)
    {
        return response()->json([
            'message' => 'Payment transaction updated successfully',
            'data' => new PaymentTransactionTableResource($this->repo->update((int) $id, $request->validated())),
        ]);
    }
}
