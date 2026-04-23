<?php

namespace App\Modules\User\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\User\Repositories\Interfaces\CustomerRepositoryInterface;
use App\Modules\User\Requests\Customer\CustomersIndexRequest;
use App\Modules\User\Resources\CustomerDetailResource;
use App\Modules\User\Resources\CustomerTableResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function __construct(
        protected CustomerRepositoryInterface $repo
    ) {}

    public function index(CustomersIndexRequest $request): JsonResponse
    {
        $data = $request->validated();
        $search = $data['search'] ?? null;
        $rowsPerPage = (int) ($data['rowsPerPage'] ?? 10);
        $customers = $this->repo->getAllByStore($this->resolveStoreId($request), $search, $rowsPerPage);

        return response()->json([
            'data' => CustomerTableResource::collection($customers->items()),
            'meta' => [
                'total' => $customers->total(),
                'per_page' => $customers->perPage(),
                'current_page' => $customers->currentPage(),
                'last_page' => $customers->lastPage(),
                'from' => $customers->firstItem(),
                'to' => $customers->lastItem(),
            ],
            'links' => [
                'first' => $customers->url(1),
                'last' => $customers->url($customers->lastPage()),
                'prev' => $customers->previousPageUrl(),
                'next' => $customers->nextPageUrl(),
            ],
        ]);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $customer = $this->repo->findForStoreWithDetails($this->resolveStoreId($request), $id);

        if (!$customer) {
            return response()->json(['message' => 'Customer not found'], 404);
        }

        return response()->json([
            'data' => new CustomerDetailResource($customer),
        ]);
    }

    private function resolveStoreId(Request $request): string
    {
        $user = $request->user();
        abort_if(!$user, 401, 'Unauthenticated.');

        $store = $user->store()->first();
        abort_if(!$store, 404, 'No store is linked to the authenticated user.');

        return (string) $store->id;
    }
}
