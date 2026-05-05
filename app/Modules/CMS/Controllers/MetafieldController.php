<?php

namespace App\Modules\CMS\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\CMS\Controllers\Concerns\RespondsWithCmsPaginator;
use App\Modules\CMS\Repositories\Interfaces\MetafieldsRepositoryInterface;
use App\Modules\CMS\Requests\MetafieldsIndexRequest;
use App\Modules\CMS\Requests\UpdateMetafieldRequest;
use App\Modules\CMS\Resources\MetafieldTableResource;
use App\Modules\Shopify\OutboundSync\Services\LocalChangeOutboundSyncDispatcher;

class MetafieldController extends Controller
{
    use RespondsWithCmsPaginator;

    public function __construct(
        protected MetafieldsRepositoryInterface $repo,
        protected LocalChangeOutboundSyncDispatcher $outboundSyncDispatcher,
    ) {}

    public function index(MetafieldsIndexRequest $request)
    {
        $data = $request->validated();

        return $this->paginatedResponse(
            $this->repo->all($data['search'] ?? null, $data['rowsPerPage'] ?? 10, $data['page'] ?? 1, $this->requestFilters($data)),
            MetafieldTableResource::class,
        );
    }

    public function show(int $id)
    {
        return response()->json([
            'data' => new MetafieldTableResource($this->repo->findForFrontend($id)),
        ]);
    }

    public function update(UpdateMetafieldRequest $request, int $id)
    {
        $validated = $request->validated();
        $updated = $this->repo->update($id, $validated);
        $outboundSyncId = $this->outboundSyncDispatcher->dispatchFromValidated(
            validated: $validated,
            storeId: (string) $updated->store_id,
            entityType: 'metafield',
            entityId: (string) $updated->id,
            action: 'update',
        );

        return response()->json([
            'message' => 'Metafield updated successfully',
            'data' => new MetafieldTableResource($updated),
            'meta' => [
                'outbound_sync_id' => $outboundSyncId,
            ],
        ]);
    }

    public function store(UpdateMetafieldRequest $request)
    {
        $validated = $request->validated();
        $created = $this->repo->create($validated);
        $outboundSyncId = $this->outboundSyncDispatcher->dispatchFromValidated(
            validated: $validated,
            storeId: (string) $created->store_id,
            entityType: 'metafield',
            entityId: (string) $created->id,
            action: 'create',
        );

        return response()->json([
            'message' => 'Metafield created successfully',
            'data' => new MetafieldTableResource($created),
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

        $metafield = $this->repo->findForFrontend($id);
        $storeId = (string) $metafield->store_id;
        $entityId = (string) $metafield->id;
        $this->repo->delete($id);

        $outboundSyncId = $this->outboundSyncDispatcher->dispatchFromValidated(
            validated: $validated,
            storeId: $storeId,
            entityType: 'metafield',
            entityId: $entityId,
            action: 'delete',
        );

        return response()->json([
            'message' => 'Metafield deleted successfully',
            'meta' => ['outbound_sync_id' => $outboundSyncId],
        ]);
    }
}
