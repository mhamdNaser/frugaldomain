<?php

namespace App\Modules\MobileApp\Repositories\Interfaces;

interface MobileStorefrontRepositoryInterface
{
    public function bootstrap(string $storeId, ?string $warehouseName = null): array;
    public function navigation(string $storeId, array $filters = []): array;
    public function listProducts(string $storeId, array $filters = [], ?string $warehouseName = null);
    public function findProduct(string $storeId, int $productId, ?string $warehouseName = null);
    public function listCollections(string $storeId, array $filters = [], ?string $warehouseName = null);
    public function findCollection(string $storeId, int $collectionId, ?string $warehouseName = null);
    public function listPages(string $storeId, array $filters = []);
    public function findPage(string $storeId, int $pageId);
    public function listBlogs(string $storeId, array $filters = []);
    public function listArticlesByBlog(string $storeId, int $blogId, array $filters = []);
    public function search(string $storeId, string $q, int $limit = 20, ?string $warehouseName = null): array;
    public function paymentMethods(string $storeId): array;
    public function checkoutQuote(string $storeId, array $lines, ?string $warehouseName = null): array;
    public function createDraftOrder(string $storeId, array $payload, ?string $warehouseName = null): array;
}
