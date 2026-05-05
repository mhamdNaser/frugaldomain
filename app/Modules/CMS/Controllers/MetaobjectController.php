<?php

namespace App\Modules\CMS\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\CMS\Controllers\Concerns\RespondsWithCmsPaginator;
use App\Modules\CMS\Repositories\Interfaces\MetaobjectsRepositoryInterface;
use App\Modules\CMS\Requests\MetaobjectsIndexRequest;
use App\Modules\CMS\Requests\UpdateMetaobjectRequest;
use App\Modules\CMS\Resources\MetaobjectTableResource;
use App\Modules\Shopify\OutboundSync\Services\LocalChangeOutboundSyncDispatcher;

class MetaobjectController extends Controller
{
    use RespondsWithCmsPaginator;

    public function __construct(
        protected MetaobjectsRepositoryInterface $repo,
        protected LocalChangeOutboundSyncDispatcher $outboundSyncDispatcher,
    ) {}

    public function index(MetaobjectsIndexRequest $request)
    {
        $data = $request->validated();

        return $this->paginatedResponse(
            $this->repo->all($data['search'] ?? null, $data['rowsPerPage'] ?? 10, $data['page'] ?? 1, $this->requestFilters($data)),
            MetaobjectTableResource::class,
        );
    }

    public function show(int $id)
    {
        return response()->json([
            'data' => new MetaobjectTableResource($this->repo->findForFrontend($id)),
        ]);
    }

    public function update(UpdateMetaobjectRequest $request, int $id)
    {
        $validated = $request->validated();
        $updated = $this->repo->update($id, $validated);
        $outboundSyncId = $this->outboundSyncDispatcher->dispatchFromValidated(
            validated: $validated,
            storeId: (string) $updated->store_id,
            entityType: 'metaobject',
            entityId: (string) $updated->id,
            action: 'update',
        );

        return response()->json([
            'message' => 'Metaobject updated successfully',
            'data' => new MetaobjectTableResource($updated),
            'meta' => [
                'outbound_sync_id' => $outboundSyncId,
            ],
        ]);
    }

    public function store(UpdateMetaobjectRequest $request)
    {
        $validated = $request->validated();
        $created = $this->repo->create($validated);
        $outboundSyncId = $this->outboundSyncDispatcher->dispatchFromValidated(
            validated: $validated,
            storeId: (string) $created->store_id,
            entityType: 'metaobject',
            entityId: (string) $created->id,
            action: 'create',
        );

        return response()->json([
            'message' => 'Metaobject created successfully',
            'data' => new MetaobjectTableResource($created),
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

        $metaobject = $this->repo->findForFrontend($id);
        $storeId = (string) $metaobject->store_id;
        $entityId = (string) $metaobject->id;
        $this->repo->delete($id);

        $outboundSyncId = $this->outboundSyncDispatcher->dispatchFromValidated(
            validated: $validated,
            storeId: $storeId,
            entityType: 'metaobject',
            entityId: $entityId,
            action: 'delete',
        );

        return response()->json([
            'message' => 'Metaobject deleted successfully',
            'meta' => ['outbound_sync_id' => $outboundSyncId],
        ]);
    }
}
