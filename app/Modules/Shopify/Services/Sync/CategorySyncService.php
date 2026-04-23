<?php

namespace App\Modules\Shopify\Services\Sync;

use App\Modules\Catalog\Models\Category;
use App\Modules\Stores\Models\Store;
use Illuminate\Support\Str;

class CategorySyncService
{
    /**
     * مزامنة بيانات الفيندور
     *
     * @return int|null معرف الفيندور بعد المزامنة
     */
    public function sync(Store $store, ?string $categoryId, ?string $categoryName): ?int
    {
        if (empty($categoryName)) {
            return null;
        }

        $category = Category::updateOrCreate(
            [
                'store_id' => $store->id,
                'shopify_category_id' => $categoryId,
            ],
            [
                'name' => $categoryName,
                'slug' => Str::slug($categoryName ?? ''),
            ]
        );

        return $category->id;
    }
}
