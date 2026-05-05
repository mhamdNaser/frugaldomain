<?php

namespace App\Modules\MobileApp\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\MobileApp\Requests\MobileCheckoutPlaceOrderRequest;
use App\Modules\MobileApp\Requests\MobileCheckoutQuoteRequest;
use App\Modules\MobileApp\Requests\MobileStoreRequest;
use App\Modules\MobileApp\Resources\MobileArticleResource;
use App\Modules\MobileApp\Resources\MobileBlogResource;
use App\Modules\MobileApp\Resources\MobileCollectionResource;
use App\Modules\MobileApp\Resources\MobilePageResource;
use App\Modules\MobileApp\Resources\MobileProductResource;
use App\Modules\MobileApp\Resources\MobileStoreResource;
use App\Modules\MobileApp\Services\MobileStorefrontService;
use Illuminate\Http\JsonResponse;

class MobileStorefrontController extends Controller
{
    public function __construct(private readonly MobileStorefrontService $service) {}

    public function bootstrap(MobileStoreRequest $request): JsonResponse
    {
        $storeId = (string) $request->validated('store_id');
        $warehouseName = $this->warehouseToken($request->validated());
        $data = $this->service->bootstrap($storeId, $warehouseName);

        return response()->json([
            'status' => 'success',
            'data' => [
                'store' => new MobileStoreResource($data['store']),
                'featured_products' => MobileProductResource::collection($data['featured_products']),
                'collections' => MobileCollectionResource::collection($data['collections']),
                'pages' => MobilePageResource::collection($data['pages']),
                'payment_methods' => $data['payment_methods'],
            ],
        ]);
    }

    public function navigation(MobileStoreRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $data = $this->service->navigation((string) $validated['store_id'], $validated);

        return response()->json([
            'status' => 'success',
            'data' => $data,
        ]);
    }

    public function products(MobileStoreRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $warehouseToken = $this->warehouseToken($validated);
        $items = $this->service->products(
            (string) $validated['store_id'],
            $validated,
            $warehouseToken
        );

        return response()->json([
            'status' => 'success',
            'data' => MobileProductResource::collection($items),
            'meta' => [
                'current_page' => $items->currentPage(),
                'last_page' => $items->lastPage(),
                'per_page' => $items->perPage(),
                'total' => $items->total(),
            ],
        ]);
    }

    public function productDetails(MobileStoreRequest $request, int $id): JsonResponse
    {
        $validated = $request->validated();
        $warehouseToken = $this->warehouseToken($validated);
        $item = $this->service->productDetails(
            (string) $validated['store_id'],
            $id,
            $warehouseToken
        );

        return response()->json([
            'status' => 'success',
            'data' => new MobileProductResource($item),
        ]);
    }

    public function collections(MobileStoreRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $warehouseToken = $this->warehouseToken($validated);
        $items = $this->service->collections(
            (string) $validated['store_id'],
            $validated,
            $warehouseToken
        );

        return response()->json([
            'status' => 'success',
            'data' => MobileCollectionResource::collection($items),
            'meta' => [
                'current_page' => $items->currentPage(),
                'last_page' => $items->lastPage(),
                'per_page' => $items->perPage(),
                'total' => $items->total(),
            ],
        ]);
    }

    public function collectionDetails(MobileStoreRequest $request, int $id): JsonResponse
    {
        $validated = $request->validated();
        $warehouseToken = $this->warehouseToken($validated);
        $item = $this->service->collectionDetails(
            (string) $validated['store_id'],
            $id,
            $warehouseToken
        );

        return response()->json([
            'status' => 'success',
            'data' => new MobileCollectionResource($item),
        ]);
    }

    public function pages(MobileStoreRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $items = $this->service->pages((string) $validated['store_id'], $validated);

        return response()->json([
            'status' => 'success',
            'data' => MobilePageResource::collection($items),
            'meta' => [
                'current_page' => $items->currentPage(),
                'last_page' => $items->lastPage(),
                'per_page' => $items->perPage(),
                'total' => $items->total(),
            ],
        ]);
    }

    public function pageDetails(MobileStoreRequest $request, int $id): JsonResponse
    {
        $validated = $request->validated();
        $item = $this->service->pageDetails((string) $validated['store_id'], $id);

        return response()->json([
            'status' => 'success',
            'data' => new MobilePageResource($item),
        ]);
    }

    public function blogs(MobileStoreRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $items = $this->service->blogs((string) $validated['store_id'], $validated);

        return response()->json([
            'status' => 'success',
            'data' => MobileBlogResource::collection($items),
            'meta' => [
                'current_page' => $items->currentPage(),
                'last_page' => $items->lastPage(),
                'per_page' => $items->perPage(),
                'total' => $items->total(),
            ],
        ]);
    }

    public function blogArticles(MobileStoreRequest $request, int $blogId): JsonResponse
    {
        $validated = $request->validated();
        $items = $this->service->blogArticles((string) $validated['store_id'], $blogId, $validated);

        return response()->json([
            'status' => 'success',
            'data' => MobileArticleResource::collection($items),
            'meta' => [
                'current_page' => $items->currentPage(),
                'last_page' => $items->lastPage(),
                'per_page' => $items->perPage(),
                'total' => $items->total(),
            ],
        ]);
    }

    public function search(MobileStoreRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $warehouseToken = $this->warehouseToken($validated);
        $q = trim((string) ($validated['q'] ?? ''));
        abort_if($q === '', 422, 'Search query is required.');

        $result = $this->service->search(
            (string) $validated['store_id'],
            $q,
            (int) ($validated['limit'] ?? 20),
            $warehouseToken
        );

        return response()->json([
            'status' => 'success',
            'data' => [
                'products' => MobileProductResource::collection($result['products']),
                'collections' => MobileCollectionResource::collection($result['collections']),
                'pages' => MobilePageResource::collection($result['pages']),
            ],
        ]);
    }

    public function paymentMethods(MobileStoreRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $result = $this->service->paymentMethods((string) $validated['store_id']);

        return response()->json([
            'status' => 'success',
            'data' => $result,
        ]);
    }

    public function checkoutQuote(MobileCheckoutQuoteRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $result = $this->service->checkoutQuote(
            (string) $validated['store_id'],
            $validated['lines'],
            $validated['warehouse_name'] ?? null
        );

        return response()->json([
            'status' => 'success',
            'data' => $result,
        ]);
    }

    public function placeDraftOrder(MobileCheckoutPlaceOrderRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $result = $this->service->placeDraftOrder(
            (string) $validated['store_id'],
            $validated,
            $validated['warehouse_name'] ?? null
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Draft order created successfully.',
            'data' => $result,
        ], 201);
    }

    private function warehouseToken(array $validated): ?string
    {
        $token = trim((string) (
            $validated['warehouse_id']
            ?? $validated['warehouse_name']
            ?? $validated['warehouse_location']
            ?? ''
        ));

        return $token === '' ? null : $token;
    }
}
