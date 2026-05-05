<?php

namespace App\Modules\Catalog\Controllers\References;

use App\Http\Controllers\Controller;
use App\Modules\Catalog\Repositories\Interfaces\References\TagsRepositoryInterface;
use App\Modules\Catalog\Requests\References\TagsIndexRequest;
use App\Modules\Catalog\Requests\References\UpdateTagRequest;
use App\Modules\Catalog\Resources\References\TagTableResource;
use App\Modules\Shopify\OutboundSync\Services\LocalChangeOutboundSyncDispatcher;

class TagsController extends Controller
{
    public function __construct(
        protected TagsRepositoryInterface $repo,
        protected LocalChangeOutboundSyncDispatcher $outboundSyncDispatcher,
    ) {}

    public function index(TagsIndexRequest $request)
    {
        $data = $request->validated();
        $search = $data['search'] ?? null;
        $rowsPerPage = $data['rowsPerPage'] ?? 10;
        $page = $data['page'] ?? 1;

        $result = $this->repo->all($search, $rowsPerPage, $page);

        return response()->json([
            'data' => TagTableResource::collection($result->items()),
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
            'data' => new TagTableResource($this->repo->findForFrontend((int) $id)),
        ]);
    }

    public function update(UpdateTagRequest $request, $id)
    {
        $validated = $request->validated();
        $updated = $this->repo->update((int) $id, $validated);
        $outboundSyncId = $this->outboundSyncDispatcher->dispatchFromValidated(
            validated: $validated,
            storeId: (string) $updated->store_id,
            entityType: 'tag',
            entityId: (string) $updated->id,
            action: 'update',
        );

        return response()->json([
            'message' => 'Tag updated successfully',
            'data' => new TagTableResource($updated),
            'meta' => [
                'outbound_sync_id' => $outboundSyncId,
            ],
        ]);
    }

    public function store()
    {
        $validated = request()->validate([
            'store_id' => ['required', 'uuid'],
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255'],
            'shopify_sync' => ['sometimes', 'array'],
            'shopify_sync.mutation' => ['sometimes', 'required_without:shopify_sync.query', 'string'],
            'shopify_sync.query' => ['sometimes', 'required_without:shopify_sync.mutation', 'string'],
            'shopify_sync.variables' => ['nullable', 'array'],
            'shopify_sync.resource_path' => ['nullable', 'string', 'max:255'],
            'shopify_sync.user_errors_path' => ['nullable', 'string', 'max:255'],
            'shopify_sync.idempotency_key' => ['nullable', 'string', 'max:255'],
            'shopify_sync.correlation_id' => ['nullable', 'string', 'max:255'],
            'shopify_sync.priority' => ['nullable', 'integer', 'min:0', 'max:9'],
            'shopify_sync.max_attempts' => ['nullable', 'integer', 'min:1', 'max:20'],
        ]);

        $created = $this->repo->create($validated);
        $outboundSyncId = $this->outboundSyncDispatcher->dispatchFromValidated(
            validated: $validated,
            storeId: (string) $created->store_id,
            entityType: 'tag',
            entityId: (string) $created->id,
            action: 'create',
        );

        return response()->json([
            'message' => 'Tag created successfully',
            'data' => new TagTableResource($created),
            'meta' => ['outbound_sync_id' => $outboundSyncId],
        ], 201);
    }

    public function destroy(int $id)
    {
        $validated = request()->validate([
            'shopify_sync' => ['sometimes', 'array'],
            'shopify_sync.mutation' => ['sometimes', 'required_without:shopify_sync.query', 'string'],
            'shopify_sync.query' => ['sometimes', 'required_without:shopify_sync.mutation', 'string'],
            'shopify_sync.variables' => ['nullable', 'array'],
            'shopify_sync.resource_path' => ['nullable', 'string', 'max:255'],
            'shopify_sync.user_errors_path' => ['nullable', 'string', 'max:255'],
            'shopify_sync.idempotency_key' => ['nullable', 'string', 'max:255'],
            'shopify_sync.correlation_id' => ['nullable', 'string', 'max:255'],
            'shopify_sync.priority' => ['nullable', 'integer', 'min:0', 'max:9'],
            'shopify_sync.max_attempts' => ['nullable', 'integer', 'min:1', 'max:20'],
        ]);

        $tag = $this->repo->find((int) $id);
        $storeId = (string) $tag->store_id;
        $entityId = (string) $tag->id;
        $this->repo->delete((int) $id);

        $outboundSyncId = $this->outboundSyncDispatcher->dispatchFromValidated(
            validated: $validated,
            storeId: $storeId,
            entityType: 'tag',
            entityId: $entityId,
            action: 'delete',
        );

        return response()->json([
            'message' => 'Tag deleted successfully',
            'meta' => ['outbound_sync_id' => $outboundSyncId],
        ]);
    }
}
