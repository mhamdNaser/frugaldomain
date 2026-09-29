<?php

namespace App\Modules\Analytics\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Resolves a visitor's country (and city when known).
 *
 * 1. Country headers injected by the CDN / host (Cloudflare, Vercel, Hostinger hCDN, ...).
 * 2. Otherwise ip-api.com (free tier: HTTP only, 45 req/min), cached per IP hash.
 *
 * Every failure resolves to nulls; tracking must never break because of geo lookups.
 */
class GeoLocator
{
    private const EXPLICIT_HEADERS = [
        'CF-IPCountry',
        'X-Country-Code',
        'X-Vercel-IP-Country',
        'X-Geo-Country',
        'X-Hcdn-Country',
        'X-Country',
        'CloudFront-Viewer-Country',
    ];

    private const CITY_HEADERS = ['CF-IPCity', 'X-Vercel-IP-City', 'X-Geo-City', 'X-City'];

    private const COOLDOWN_KEY = 'analytics:geo:cooldown';

    private const EMPTY = ['country_code' => null, 'country_name' => null, 'city' => null];

    /**
     * @return array{country_code: ?string, country_name: ?string, city: ?string}
     */
    public function locate(Request $request, ?string $ip, string $ipHash): array
    {
        $code = $this->countryFromHeaders($request);
        if ($code !== null) {
            return [
                'country_code' => $code,
                'country_name' => $this->countryName($code),
                'city' => $this->cityFromHeaders($request),
            ];
        }

        if (! $this->isPublicIp($ip)) {
            return self::EMPTY;
        }

        $cacheKey = 'analytics:geo:' . $ipHash;
        $cached = Cache::get($cacheKey);
        if (is_array($cached)) {
            return $cached + self::EMPTY;
        }

        if (Cache::has(self::COOLDOWN_KEY)) {
            return self::EMPTY;
        }

        $result = $this->lookupIpApi($ip);

        // Successful lookups are cached for a week; failures only briefly so they can recover.
        Cache::put($cacheKey, $result ?? self::EMPTY, $result ? now()->addDays(7) : now()->addHour());

        return $result ?? self::EMPTY;
    }

    public function isPublicIp(?string $ip): bool
    {
        return $ip !== null && filter_var(
            $ip,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
        ) !== false;
    }

    private function countryFromHeaders(Request $request): ?string
    {
        foreach (self::EXPLICIT_HEADERS as $header) {
            if ($code = $this->normaliseCode($request->headers->get($header))) {
                return $code;
            }
        }

        // Unknown CDNs (e.g. Hostinger hCDN): accept any header whose name mentions "country".
        foreach ($request->headers->all() as $name => $values) {
            if (str_contains(strtolower($name), 'country')) {
                if ($code = $this->normaliseCode($values[0] ?? null)) {
                    return $code;
                }
            }
        }

        return null;
    }

    private function cityFromHeaders(Request $request): ?string
    {
        foreach (self::CITY_HEADERS as $header) {
            $value = trim(rawurldecode((string) $request->headers->get($header)));
            if ($value !== '') {
                return mb_substr($value, 0, 190);
            }
        }

        return null;
    }

    private function normaliseCode(?string $value): ?string
    {
        $value = strtoupper(trim((string) $value));

        // XX/ZZ = unknown, T1 = Tor (Cloudflare), EU/AP = non-country regions.
        if (! preg_match('/^[A-Z]{2}$/', $value) || in_array($value, ['XX', 'T1', 'EU', 'AP', 'ZZ'], true)) {
            return null;
        }

        return $value;
    }

    private function countryName(string $code): ?string
    {
        if (class_exists(\Locale::class)) {
            $name = \Locale::getDisplayRegion('-' . $code, 'en');

            return ($name !== '' && $name !== $code) ? $name : null;
        }

        // The admin UI localises names from the code via Intl.DisplayNames anyway.
        return null;
    }

    /**
     * @return array{country_code: ?string, country_name: ?string, city: ?string}|null
     */
    private function lookupIpApi(string $ip): ?array
    {
        try {
            $response = Http::timeout(2)
                ->connectTimeout(2)
                ->acceptJson()
                ->get('http://ip-api.com/json/' . rawurlencode($ip), [
                    'fields' => 'status,country,countryCode,city',
                ]);

            // ip-api reports how many requests remain in the current minute; back off at zero.
            if ($response->status() === 429 || $response->header('X-Rl') === '0') {
                $ttl = max(5, (int) ($response->header('X-Ttl') ?: 60));
                Cache::put(self::COOLDOWN_KEY, true, now()->addSeconds($ttl));
            }

            if (! $response->successful() || $response->json('status') !== 'success') {
                return null;
            }

            $code = $this->normaliseCode($response->json('countryCode'));
            $country = mb_substr((string) $response->json('country'), 0, 190);
            $city = mb_substr((string) $response->json('city'), 0, 190);

            return [
                'country_code' => $code,
                'country_name' => ($code && $country !== '') ? $country : null,
                'city' => $city !== '' ? $city : null,
            ];
        } catch (Throwable) {
            return null;
        }
    }
}
