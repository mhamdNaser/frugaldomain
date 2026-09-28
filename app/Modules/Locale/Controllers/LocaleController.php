<?php

namespace App\Modules\Locale\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;

class LocaleController extends Controller
{
    public function setlocale($lang)
    {
        // The slug becomes part of a file path, so only allow plain language codes.
        if (! preg_match('/^[A-Za-z]{2,3}([_-][A-Za-z]{2,4})?$/', $lang)) {
            return response()->json(['message' => 'Invalid language'], 422);
        }

        App::setLocale($lang);

        $adminPath = resource_path("lang/{$lang}/admin.php");
        $sitePath = resource_path("lang/{$lang}/site.php");

        // The files' modification times are part of the key, so any edit (from the
        // dashboard, FTP or a deploy) is served immediately instead of after 24h.
        $stamp = fn ($path) => file_exists($path) ? filemtime($path) . '-' . filesize($path) : '0';
        $cacheKey = 'translations_all_' . $lang . '_' . $stamp($adminPath) . '_' . $stamp($sitePath);

        $payload = Cache::remember($cacheKey, 86400, function () use ($lang, $adminPath, $sitePath) {
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
