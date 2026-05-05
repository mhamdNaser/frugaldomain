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

        $cacheKey = 'translations_all_' . $lang . '_v3';

        $payload = Cache::remember($cacheKey, 86400, function () use ($lang) {
            $adminPath = resource_path("lang/{$lang}/admin.php");
            $sitePath = resource_path("lang/{$lang}/site.php");

            $admin = file_exists($adminPath) ? require $adminPath : [];
            $site = file_exists($sitePath) ? require $sitePath : [];

            // Backward-compatible flat object + structured namespaces
            return array_merge($site, $admin, [
                'site' => $site,
                'admin' => $admin,
                '__meta' => [
                    'language' => $lang,
                    'generated_at' => now()->toIso8601String(),
                ],
            ]);
        });

        return response()->json($payload)->withHeaders([
            'Cache-Control' => 'public, max-age=3600',
            'X-Cache-Status' => Cache::has($cacheKey) ? 'HIT' : 'MISS',
        ]);
    }
}
