<?php

namespace App\Modules\Icon\Repositories\Eloquent;

use App\Modules\Icon\Models\Icon;
use App\Modules\Icon\Models\IconFiles;
use App\Modules\Icon\Repositories\Interfaces\IconRepositoryInterface;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Imagick\Driver as ImagickDriver;
use Intervention\Image\Drivers\Gd\Driver as GdDriver;
use Intervention\Image\Encoders\PngEncoder;
use Illuminate\Support\Str;
use App\Traits\ManageFiles;
use App\Traits\PaginatesCollection;
use Illuminate\Support\Facades\Cache;

class IconRepository implements IconRepositoryInterface
{
    use ManageFiles;
    use PaginatesCollection;

    protected $model;

    public function __construct(Icon $icon)
    {
        $this->model = $icon;
    }

    public function all($search = null, $rowsPerPage = 10, $page = 1)
    {
        $cacheKey = "icon_all";

        // نحصل على كل البيانات من الكاش أو قاعدة البيانات
        $items = Cache::remember($cacheKey, 60, function () {
            return Icon::orderBy('id', 'desc')
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

    public function allWithoutPagination($search = null, $category = null)
    {
        // Cache key يشمل الاثنين
        $cacheKey = "icon_all_WithoutPagination_"
            . ($category ?? 'allCategories') . "_"
            . ($search ?? 'allSearch');

        return Cache::remember($cacheKey, 60, function () use ($search, $category) {
            $query = Icon::query()->with('category');

            // فلترة الكاتيجوري
            if ($category) {
                $query->whereHas('category', function ($q) use ($category) {
                    $q->where('name', $category);
                });
            }

            // فلترة البحث على النصوص
            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%")
                        ->orWhere('tags', 'like', "%{$search}%");
                });
            }

            return $query->orderBy('id', 'desc')->get();
        });
    }

    public function find(int $id)
    {
        return $this->model->with('category', 'user')->findOrFail($id);
    }

    public function create(array $data)
    {
        $slug = Str::slug($data['title']);
        $storagePath = public_path('icons');
        if (!file_exists($storagePath)) {
            mkdir($storagePath, 0755, true);
        }

        // إنشاء الأيقونة
        $icon = $this->model->create([
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'category_id' => $data['category_id'] ?? null,
            'user_id' => $data['user_id'] ?? null,
            'is_premium' => $data['is_premium'] ?? false,
            'is_active' => $data['is_active'] ?? true,
            'tags' => $data['tags'] ?? [],
        ]);

        // حفظ SVG كنص
        $svgPath = $this->uploadFile($data['icon_text'], 'icons', $slug, 'svg');

        // 🔸 حساب الحجم من الملف الفعلي
        $svgFullPath = public_path($svgPath);
        $svgSize = file_exists($svgFullPath) ? filesize($svgFullPath) : 0;

        // 🔸 SVG غالباً ما فيها width/height فعلي، نتركها null
        IconFiles::create([
            'icon_id' => $icon->id,
            'file_name' => $slug . '.svg',
            'file_path' => $svgPath,
            'file_type' => 'svg',
            'file_size' => $svgSize,
            'dimensions' => null,
        ]);

        // تحويل SVG إلى PNG
        $driver = extension_loaded('imagick') ? new ImagickDriver() : new GdDriver();
        $manager = new ImageManager($driver);
        $image = $manager->read($data['icon_text']);
        $encoded = $image->encode(new PngEncoder());
        $pngPath = $this->uploadFile($encoded, 'icons', $slug, 'png');

        // 🔸 حساب الحجم والأبعاد من الملف الفعلي
        $pngFullPath = public_path($pngPath);
        $pngSize = file_exists($pngFullPath) ? filesize($pngFullPath) : 0;
        $pngDimensions = null;
        if (file_exists($pngFullPath)) {
            $info = getimagesize($pngFullPath);
            $pngDimensions = $info ? "{$info[0]}x{$info[1]}" : null;
        }

        IconFiles::create([
            'icon_id' => $icon->id,
            'file_name' => $slug . '.png',
            'file_path' => $pngPath,
            'file_type' => 'png',
            'file_size' => $pngSize,
            'dimensions' => $pngDimensions,
        ]);

        Cache::forget('icon_all');

        return $icon->load('files');
    }

    public function update(int $id, array $data)
    {
        Cache::forget('icon_all');
        $icon = $this->find($id);
        $icon->update($data);
        return $icon;
    }

    public function delete($id)
    {
        Cache::forget('icon_all');
        $icon = $this->find($id);
        return $icon->delete();
    }

    public function deleteArray(array $ids)
    {
        Cache::forget('icon_all');
        return $this->model->destroy($ids);
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
