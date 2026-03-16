<?php

namespace App\Modules\Locale\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;

class LocaleController extends Controller
{
    public function setlocale($lang)
    {
        App::setLocale($lang);

        // مفتاح الكاش
        $cacheKey = 'translations_admin_' . $lang . '_v2';

        // جلب الترجمات من الكاش
        $translations = Cache::remember($cacheKey, 86400, function () use ($lang) {
            // جلب الترجمات بشكل أسرع باستخدام json
            $path = resource_path("lang/{$lang}/admin.php");

            if (file_exists($path)) {
                return require $path;
            }

            return trans('admin');
        });

        // إضافة معلومات الكاش للـ response
        return response()->json($translations)
            ->withHeaders([
                'Cache-Control' => 'public, max-age=3600',
                'X-Cache-Status' => Cache::has($cacheKey) ? 'HIT' : 'MISS'
            ]);
    }
}
