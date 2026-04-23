<?php

namespace App\Modules\Catalog\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Catalog\Services\ProductsDashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class ProductsDashboardController extends Controller
{
    public function __construct(
        private readonly ProductsDashboardService $dashboardService,
    ) {
    }

    public function statistics(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            if (!$user) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Unauthenticated.',
                ], 401);
            }

            $data = $this->dashboardService->getStatisticsForUser($user);

            return response()->json([
                'status' => 'success',
                'message' => 'Shopify dashboard statistics retrieved successfully.',
                'data' => $data,
            ]);
        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'status' => 'error',
                'message' => 'Server Error',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
