<?php

namespace App\Modules\Icon\Repositories\Eloquent;

use App\Modules\Icon\Models\IconCategories;
use App\Modules\Icon\Repositories\Interfaces\IconCategoryRepositoryInterface;
use App\Traits\PaginatesCollection;
use Illuminate\Support\Facades\Cache;

class IconCategoryRepository implements IconCategoryRepositoryInterface
{

    use PaginatesCollection;

    public function all($search = null, $rowsPerPage = 10, $page = 1)
    {
        $cacheKey = "icon_categories_all";

        // نحصل على كل البيانات من الكاش أو قاعدة البيانات
        $items = Cache::remember($cacheKey, 60, function () {
            return IconCategories::orderBy('id', 'desc')->get();
        });

        // تطبيق الفلترة على الكولكشن
        if ($search) {
            $items = $items->filter(function ($item) use ($search) {
                return stripos($item->name, $search) !== false;
            });
        }

        // استخدام التريت لتطبيق الباجنيشن
        return $this->paginate($items, $rowsPerPage, $page);
    }

    public function allWithoutPagination()
    {
        return IconCategories::select('id', 'name')
            ->where('is_active', true)
            ->orderBy('name', 'asc')
            ->get();
    }

    public function find($id)
    {
        return IconCategories::findOrFail($id);
    }

    public function create(array $data)
    {
        Cache::forget('icon_categories_all');
        return IconCategories::create($data);
    }

    public function update($id, array $data)
    {
        Cache::forget('icon_categories_all');

        $category = $this->find($id);
        $category->update($data);
        return $category;
    }

    public function delete($id)
    {
        Cache::forget('icon_categories_all');
        $category = $this->find($id);
        $category->delete();
        return true;
    }

    public function deleteArray(array $ids)
    {
        Cache::forget('icon_categories_all');

        return IconCategories::whereIn('id', $ids)->delete();
    }

    public function changeStatus($id)
    {
        Cache::forget('icon_categories_all');
        $category = $this->find($id);
        $category->is_active = !$category->is_active;
        $category->save();
        return $category;
    }
}
