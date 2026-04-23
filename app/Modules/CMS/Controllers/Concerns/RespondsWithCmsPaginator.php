<?php

namespace App\Modules\CMS\Controllers\Concerns;

trait RespondsWithCmsPaginator
{
    protected function paginatedResponse($result, string $resourceClass)
    {
        return response()->json([
            'data' => $resourceClass::collection($result->items()),
            'links' => [
                'first' => $result->url(1),
                'last' => $result->url($result->lastPage()),
                'prev' => $result->previousPageUrl(),
                'next' => $result->nextPageUrl(),
            ],
            'meta' => [
                'current_page' => $result->currentPage(),
                'from' => $result->firstItem(),
                'last_page' => $result->lastPage(),
                'per_page' => $result->perPage(),
                'to' => $result->lastItem(),
                'total' => $result->total(),
            ],
        ]);
    }

    protected function requestFilters(array $data): array
    {
        return [
            $data['parent_field'] ?? '' => $data['parent_id'] ?? null,
            'metaobject_id' => ($data['parent_field'] ?? null) === 'metaobject_id' ? ($data['parent_id'] ?? null) : null,
        ];
    }
}
