<?php

namespace App\Modules\CMS\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\CMS\Controllers\Concerns\RespondsWithCmsPaginator;
use App\Modules\CMS\Repositories\Interfaces\MenusRepositoryInterface;
use App\Modules\CMS\Requests\MenusIndexRequest;
use App\Modules\CMS\Requests\UpdateMenuRequest;
use App\Modules\CMS\Resources\MenuTableResource;
use App\Modules\Shopify\OutboundSync\Services\LocalChangeOutboundSyncDispatcher;

class MenuController extends Controller
{
    use RespondsWithCmsPaginator;

    public function __construct(
        protected MenusRepositoryInterface $repo,
        protected LocalChangeOutboundSyncDispatcher $outboundSyncDispatcher,
    ) {}

    public function index(MenusIndexRequest $request)
    {
        $data = $request->validated();

        return $this->paginatedResponse(
            $this->repo->all($data['search'] ?? null, $data['rowsPerPage'] ?? 10, $data['page'] ?? 1, $this->requestFilters($data)),
            MenuTableResource::class,
        );
    }

    public function show(int $id)
    {
        return response()->json([
            'data' => new MenuTableResource($this->repo->findForFrontend($id)),
        ]);
    }

    public function update(UpdateMenuRequest $request, int $id)
    {
        $validated = $request->validated();
        $updated = $this->repo->update($id, $validated);
        $outboundSyncId = $this->outboundSyncDispatcher->dispatchFromValidated(
            validated: $validated,
            storeId: (string) $updated->store_id,
            entityType: 'menu',
            entityId: (string) $updated->id,
            action: 'update',
        );

        return response()->json([
            'message' => 'Menu updated successfully',
            'data' => new MenuTableResource($updated),
            'meta' => [
                'outbound_sync_id' => $outboundSyncId,
            ],
        ]);
    }

    public function store(UpdateMenuRequest $request)
    {
        $validated = $request->validated();
        $created = $this->repo->create($validated);
        $outboundSyncId = $this->outboundSyncDispatcher->dispatchFromValidated(
            validated: $validated,
            storeId: (string) $created->store_id,
            entityType: 'menu',
            entityId: (string) $created->id,
            action: 'create',
        );

        return response()->json([
            'message' => 'Menu created successfully',
            'data' => new MenuTableResource($created),
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

        $menu = $this->repo->findForFrontend($id);
        $storeId = (string) $menu->store_id;
        $entityId = (string) $menu->id;
        $this->repo->delete($id);

        $outboundSyncId = $this->outboundSyncDispatcher->dispatchFromValidated(
            validated: $validated,
            storeId: $storeId,
            entityType: 'menu',
            entityId: $entityId,
            action: 'delete',
        );

        return response()->json([
            'message' => 'Menu deleted successfully',
            'meta' => ['outbound_sync_id' => $outboundSyncId],
        ]);
    }
}
