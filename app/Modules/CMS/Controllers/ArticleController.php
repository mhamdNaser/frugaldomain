<?php

namespace App\Modules\CMS\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\CMS\Controllers\Concerns\RespondsWithCmsPaginator;
use App\Modules\CMS\Repositories\Interfaces\ArticlesRepositoryInterface;
use App\Modules\CMS\Requests\ArticlesIndexRequest;
use App\Modules\CMS\Requests\ArticleShowRequest;
use App\Modules\CMS\Requests\UpdateArticleRequest;
use App\Modules\CMS\Resources\ArticleDetailResource;
use App\Modules\CMS\Resources\ArticleTableResource;
use App\Modules\Shopify\OutboundSync\Services\LocalChangeOutboundSyncDispatcher;
use App\Modules\Shopify\OutboundSync\Services\ShopifyFirstSyncService;

class ArticleController extends Controller
{
    use RespondsWithCmsPaginator;

    public function __construct(
        protected ArticlesRepositoryInterface $repo,
        protected LocalChangeOutboundSyncDispatcher $outboundSyncDispatcher,
        protected ShopifyFirstSyncService $shopifyFirstSyncService,
    ) {}

    public function index(ArticlesIndexRequest $request)
    {
        $data = $request->validated();

        return $this->paginatedResponse(
            $this->repo->all($data['search'] ?? null, $data['rowsPerPage'] ?? 10, $data['page'] ?? 1, $this->requestFilters($data)),
            ArticleTableResource::class,
        );
    }

    public function show(ArticleShowRequest $request, int $id)
    {
        $data = $request->validated();

        return response()->json([
            'data' => new ArticleDetailResource($this->repo->findForFrontend((int) ($data['id'] ?? $id))),
        ]);
    }

    public function update(UpdateArticleRequest $request, int $id)
    {
        $validated = $request->validated();
        $current = $this->repo->findForFrontend($id);
        $shopifyExecuted = $this->shopifyFirstSyncService->syncOrFail($validated, (string) $current->store_id);
        $updated = $this->repo->update($id, $validated);
        $outboundSyncId = $shopifyExecuted ? null : $this->outboundSyncDispatcher->dispatchFromValidated(
            validated: $validated,
            storeId: (string) $updated->store_id,
            entityType: 'article',
            entityId: (string) $updated->id,
            action: 'update',
        );

        return response()->json([
            'message' => 'Article updated successfully',
            'data' => new ArticleDetailResource($updated),
            'meta' => [
                'outbound_sync_id' => $outboundSyncId,
            ],
        ]);
    }

    public function store(UpdateArticleRequest $request)
    {
        $validated = $request->validated();
        $shopifyExecuted = $this->shopifyFirstSyncService->syncOrFail($validated, (string) $validated['store_id']);
        $created = $this->repo->create($validated);
        $outboundSyncId = $shopifyExecuted ? null : $this->outboundSyncDispatcher->dispatchFromValidated(
            validated: $validated,
            storeId: (string) $created->store_id,
            entityType: 'article',
            entityId: (string) $created->id,
            action: 'create',
        );

        return response()->json([
            'message' => 'Article created successfully',
            'data' => new ArticleDetailResource($created),
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

        $article = $this->repo->findForFrontend($id);
        $storeId = (string) $article->store_id;
        $entityId = (string) $article->id;
        $shopifyExecuted = $this->shopifyFirstSyncService->syncOrFail($validated, $storeId);
        $this->repo->delete($id);

        $outboundSyncId = $shopifyExecuted ? null : $this->outboundSyncDispatcher->dispatchFromValidated(
            validated: $validated,
            storeId: $storeId,
            entityType: 'article',
            entityId: $entityId,
            action: 'delete',
        );

        return response()->json([
            'message' => 'Article deleted successfully',
            'meta' => ['outbound_sync_id' => $outboundSyncId],
        ]);
    }
}
