<?php

namespace App\Modules\Analytics\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Analytics\Models\SitePageView;
use App\Modules\Analytics\Models\SiteVisit;
use App\Modules\Analytics\Services\AnalyticsRange;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Super-admin reports over site_visits / site_page_views.
 *
 * SQL stays portable (MySQL in production, SQLite in tests): anything
 * dialect-specific, such as week/month bucketing, is done in PHP.
 */
class AnalyticsController extends Controller
{
    public const ONLINE_WINDOW_MINUTES = 5;

    public function overview(Request $request): JsonResponse
    {
        $range = AnalyticsRange::fromKey($request->query('range'));

        $current = $this->totals($range->start, $range->end);
        $previous = $this->totals($range->previousStart, $range->previousEnd);

        $change = [];
        foreach (['visitors', 'sessions', 'page_views', 'avg_session_seconds', 'bounce_rate'] as $metric) {
            $change[$metric] = $this->percentChange($current[$metric], $previous[$metric]);
        }

        $devices = SiteVisit::query()
            ->whereBetween('started_at', [$range->start, $range->end])
            ->select('device_type', DB::raw('COUNT(*) as sessions'), DB::raw('COUNT(DISTINCT visitor_id) as visitors'))
            ->groupBy('device_type')
            ->orderByDesc('sessions')
            ->get()
            ->map(fn ($row) => [
                'device_type' => $row->device_type ?: 'desktop',
                'sessions' => (int) $row->sessions,
                'visitors' => (int) $row->visitors,
            ])
            ->values();

        $onlineNow = SiteVisit::query()
            ->where('last_seen_at', '>=', Carbon::now()->subMinutes(self::ONLINE_WINDOW_MINUTES))
            ->distinct()
            ->count('visitor_id');

        return response()->json([
            'data' => [
                ...$range->toArray(),
                'current' => $current,
                'previous' => $previous,
                'change' => $change,
                'online_now' => $onlineNow,
                'devices' => $devices,
            ],
        ]);
    }

    public function timeseries(Request $request): JsonResponse
    {
        $range = AnalyticsRange::fromKey($request->query('range'));
        $interval = in_array($request->query('interval'), ['day', 'week', 'month'], true)
            ? $request->query('interval')
            : 'day';

        // One row per (day, visitor): small enough to bucket in PHP, and it keeps
        // "distinct visitors per week/month" exact.
        $rows = SiteVisit::query()
            ->whereBetween('started_at', [$range->start, $range->end])
            ->select(
                DB::raw('DATE(started_at) as day'),
                'visitor_id',
                DB::raw('COUNT(*) as sessions'),
                DB::raw('SUM(page_views) as page_views')
            )
            ->groupBy(DB::raw('DATE(started_at)'), 'visitor_id')
            ->toBase()
            ->cursor();

        $buckets = [];
        $cursor = $this->bucketStart($range->start, $interval);
        while ($cursor->lte($range->end)) {
            $buckets[$cursor->toDateString()] = [
                'date' => $cursor->toDateString(),
                'visitors' => [],
                'sessions' => 0,
                'page_views' => 0,
            ];
            $cursor = $this->nextBucket($cursor, $interval);
        }

        foreach ($rows as $row) {
            $key = $this->bucketStart(Carbon::parse($row->day), $interval)->toDateString();
            if (! isset($buckets[$key])) {
                continue;
            }
            $buckets[$key]['visitors'][$row->visitor_id] = true;
            $buckets[$key]['sessions'] += (int) $row->sessions;
            $buckets[$key]['page_views'] += (int) $row->page_views;
        }

        $series = array_values(array_map(fn ($b) => [
            'date' => $b['date'],
            'visitors' => count($b['visitors']),
            'sessions' => $b['sessions'],
            'page_views' => $b['page_views'],
        ], $buckets));

        return response()->json([
            'data' => [
                ...$range->toArray(),
                'interval' => $interval,
                'series' => $series,
            ],
        ]);
    }

    public function countries(Request $request): JsonResponse
    {
        $range = AnalyticsRange::fromKey($request->query('range'));
        $limit = min(100, max(1, (int) $request->query('limit', 20)));

        $base = SiteVisit::query()->whereBetween('started_at', [$range->start, $range->end]);
        $totalVisitors = (clone $base)->distinct()->count('visitor_id');

        $rows = (clone $base)
            ->select(
                'country_code',
                DB::raw('MAX(country_name) as country_name'),
                DB::raw('COUNT(DISTINCT visitor_id) as visitors'),
                DB::raw('COUNT(*) as sessions'),
                DB::raw('AVG(duration_seconds) as avg_duration')
            )
            ->groupBy('country_code')
            ->orderByDesc('visitors')
            ->limit($limit)
            ->get()
            ->map(fn ($row) => [
                'country_code' => $row->country_code,
                'country_name' => $row->country_name,
                'visitors' => (int) $row->visitors,
                'sessions' => (int) $row->sessions,
                'avg_session_seconds' => (int) round((float) $row->avg_duration),
                'share' => $totalVisitors ? round($row->visitors / $totalVisitors * 100, 1) : 0,
            ])
            ->values();

        return response()->json([
            'data' => [
                ...$range->toArray(),
                'total_visitors' => $totalVisitors,
                'countries' => $rows,
            ],
        ]);
    }

    public function visitors(Request $request): JsonResponse
    {
        $range = AnalyticsRange::fromKey($request->query('range'));
        $onlineSince = Carbon::now()->subMinutes(self::ONLINE_WINDOW_MINUTES);

        $page = SiteVisit::query()
            ->with('user:id,name,first_name,last_name,email')
            ->whereBetween('started_at', [$range->start, $range->end])
            ->orderByDesc('started_at')
            ->orderByDesc('id')
            ->paginate(20);

        $page->getCollection()->transform(function (SiteVisit $visit) use ($onlineSince) {
            $user = $visit->user;
            $fullName = $user ? trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')) : '';

            return [
                'id' => $visit->id,
                'visitor_id' => $visit->visitor_id,
                'user' => $user ? [
                    'id' => $user->id,
                    'name' => $fullName !== '' ? $fullName : $user->name,
                    'email' => $user->email,
                ] : null,
                'is_registered' => (bool) $user,
                'country_code' => $visit->country_code,
                'country_name' => $visit->country_name,
                'city' => $visit->city,
                'device_type' => $visit->device_type,
                'browser' => $visit->browser,
                'os' => $visit->os,
                'referrer' => $visit->referrer,
                'landing_path' => $visit->landing_path,
                'exit_path' => $visit->exit_path,
                'page_views' => $visit->page_views,
                'duration_seconds' => $visit->duration_seconds,
                'is_bounce' => $visit->is_bounce,
                'is_online' => $visit->last_seen_at && $visit->last_seen_at->gte($onlineSince),
                'started_at' => $visit->started_at?->toIso8601String(),
                'last_seen_at' => $visit->last_seen_at?->toIso8601String(),
            ];
        });

        return response()->json([
            'data' => $page->items(),
            'meta' => [
                'current_page' => $page->currentPage(),
                'last_page' => $page->lastPage(),
                'per_page' => $page->perPage(),
                'total' => $page->total(),
            ],
        ]);
    }

    public function pages(Request $request): JsonResponse
    {
        $range = AnalyticsRange::fromKey($request->query('range'));
        $limit = min(50, max(1, (int) $request->query('limit', 10)));

        $viewed = SitePageView::query()
            ->whereBetween('viewed_at', [$range->start, $range->end])
            ->select('path', DB::raw('COUNT(*) as views'), DB::raw('COUNT(DISTINCT session_id) as sessions'))
            ->groupBy('path')
            ->orderByDesc('views')
            ->limit($limit)
            ->get()
            ->map(fn ($row) => [
                'path' => $row->path,
                'views' => (int) $row->views,
                'sessions' => (int) $row->sessions,
            ])
            ->values();

        $landing = SiteVisit::query()
            ->whereBetween('started_at', [$range->start, $range->end])
            ->select(
                'landing_path as path',
                DB::raw('COUNT(*) as sessions'),
                DB::raw('SUM(CASE WHEN is_bounce = 1 THEN 1 ELSE 0 END) as bounces')
            )
            ->groupBy('landing_path')
            ->orderByDesc('sessions')
            ->limit($limit)
            ->get()
            ->map(fn ($row) => [
                'path' => $row->path,
                'sessions' => (int) $row->sessions,
                'bounce_rate' => $row->sessions ? round($row->bounces / $row->sessions * 100, 1) : 0,
            ])
            ->values();

        return response()->json([
            'data' => [
                ...$range->toArray(),
                'top_pages' => $viewed,
                'landing_pages' => $landing,
            ],
        ]);
    }

    private function totals(Carbon $from, Carbon $to): array
    {
        $row = SiteVisit::query()
            ->whereBetween('started_at', [$from, $to])
            ->selectRaw('COUNT(DISTINCT visitor_id) as visitors')
            ->selectRaw('COUNT(*) as sessions')
            ->selectRaw('COALESCE(SUM(page_views), 0) as page_views')
            ->selectRaw('COALESCE(AVG(duration_seconds), 0) as avg_duration')
            ->selectRaw('COALESCE(SUM(CASE WHEN is_bounce = 1 THEN 1 ELSE 0 END), 0) as bounces')
            ->selectRaw('COUNT(DISTINCT user_id) as registered_visitors')
            ->selectRaw('COALESCE(SUM(CASE WHEN user_id IS NULL THEN 0 ELSE 1 END), 0) as registered_sessions')
            ->toBase()
            ->first();

        $sessions = (int) ($row->sessions ?? 0);
        $registeredSessions = (int) ($row->registered_sessions ?? 0);

        return [
            'visitors' => (int) ($row->visitors ?? 0),
            'sessions' => $sessions,
            'page_views' => (int) ($row->page_views ?? 0),
            'avg_session_seconds' => (int) round((float) ($row->avg_duration ?? 0)),
            'bounce_rate' => $sessions ? round(((int) $row->bounces) / $sessions * 100, 1) : 0,
            'registered_visitors' => (int) ($row->registered_visitors ?? 0),
            'registered_sessions' => $registeredSessions,
            'anonymous_sessions' => $sessions - $registeredSessions,
        ];
    }

    private function percentChange(float|int $current, float|int $previous): ?float
    {
        if ((float) $previous === 0.0) {
            return (float) $current === 0.0 ? 0.0 : null;
        }

        return round(($current - $previous) / $previous * 100, 1);
    }

    private function bucketStart(Carbon $date, string $interval): Carbon
    {
        return match ($interval) {
            'week' => $date->copy()->startOfWeek(Carbon::MONDAY),
            'month' => $date->copy()->startOfMonth(),
            default => $date->copy()->startOfDay(),
        };
    }

    private function nextBucket(Carbon $date, string $interval): Carbon
    {
        return match ($interval) {
            'week' => $date->copy()->addWeek(),
            'month' => $date->copy()->addMonthNoOverflow(),
            default => $date->copy()->addDay(),
        };
    }
}
