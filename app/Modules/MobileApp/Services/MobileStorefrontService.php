<?php

namespace App\Modules\MobileApp\Services;

use App\Modules\MobileApp\Actions\ResolveMobileStoreAction;
use App\Modules\MobileApp\Repositories\Interfaces\MobileStorefrontRepositoryInterface;

class MobileStorefrontService
{
    public function __construct(
        private readonly MobileStorefrontRepositoryInterface $repository,
        private readonly ResolveMobileStoreAction $resolveStore,
    ) {}

    public function bootstrap(string $storeId, ?string $warehouseName = null): array
    {
        $this->resolveStore->execute($storeId);
        return $this->repository->bootstrap($storeId, $warehouseName);
    }

    public function navigation(string $storeId, array $filters = []): array
    {
        $this->resolveStore->execute($storeId);
        return $this->repository->navigation($storeId, $filters);
    }

    public function products(string $storeId, array $filters = [], ?string $warehouseName = null)
    {
        $this->resolveStore->execute($storeId);
        return $this->repository->listProducts($storeId, $filters, $warehouseName);
    }

    public function productDetails(string $storeId, int $productId, ?string $warehouseName = null)
    {
        $this->resolveStore->execute($storeId);
        return $this->repository->findProduct($storeId, $productId, $warehouseName);
    }

    public function collections(string $storeId, array $filters = [], ?string $warehouseName = null)
    {
        $this->resolveStore->execute($storeId);
        return $this->repository->listCollections($storeId, $filters, $warehouseName);
    }

    public function collectionDetails(string $storeId, int $collectionId, ?string $warehouseName = null)
    {
        $this->resolveStore->execute($storeId);
        return $this->repository->findCollection($storeId, $collectionId, $warehouseName);
    }

    public function pages(string $storeId, array $filters = [])
    {
        $this->resolveStore->execute($storeId);
        return $this->repository->listPages($storeId, $filters);
    }

    public function pageDetails(string $storeId, int $pageId)
    {
        $this->resolveStore->execute($storeId);
        return $this->repository->findPage($storeId, $pageId);
    }

    public function blogs(string $storeId, array $filters = [])
    {
        $this->resolveStore->execute($storeId);
        return $this->repository->listBlogs($storeId, $filters);
    }

    public function blogArticles(string $storeId, int $blogId, array $filters = [])
    {
        $this->resolveStore->execute($storeId);
        return $this->repository->listArticlesByBlog($storeId, $blogId, $filters);
    }

    public function search(string $storeId, string $query, int $limit = 20, ?string $warehouseName = null): array
    {
        $this->resolveStore->execute($storeId);
        return $this->repository->search($storeId, $query, $limit, $warehouseName);
    }

    public function paymentMethods(string $storeId): array
    {
        $this->resolveStore->execute($storeId);
        return $this->repository->paymentMethods($storeId);
    }

    public function checkoutQuote(string $storeId, array $lines, ?string $warehouseName = null): array
    {
        $this->resolveStore->execute($storeId);
        return $this->repository->checkoutQuote($storeId, $lines, $warehouseName);
    }

    public function placeDraftOrder(string $storeId, array $payload, ?string $warehouseName = null): array
    {
        $this->resolveStore->execute($storeId);
        return $this->repository->createDraftOrder($storeId, $payload, $warehouseName);
    }
}
