<?php

namespace App\Modules\Catalog\Repositories\Eloquent;

use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Repositories\Interfaces\ProductsRepositoryInterface;
use Illuminate\Support\Facades\Cache;

class ProductsRepository
{
    public function __construct(
        protected Product $model
    ) {}

    public function all($search = null, $rowsPerPage = 10, $page = 1)
    {
        $cacheKey = "Product_all";

        $items = Cache::remember($cacheKey, 60, function () {
            return Product::orderBy('id', 'desc')
                ->get();
        });

        // تطبيق الفلترة على الكولكشن
        if ($search) {
            $items = $items->filter(function ($item) use ($search) {
                return stripos($item->title, $search) !== false;
                return stripos($item->description, $search) !== false;
            });
        }

        // استخدام التريت لتطبيق الباجنيشن
        return $this->paginate($items, $rowsPerPage, $page);
    }


    public function find(int $id)
    {
        return $this->model->findOrFail($id);
    }

    public function toggleStatus(int $id)
    {
        Cache::forget('icon_all');
        $icon = $this->find($id);
        $icon->is_active = !$icon->is_active;
        $icon->save();
        return $icon;
    }
}
