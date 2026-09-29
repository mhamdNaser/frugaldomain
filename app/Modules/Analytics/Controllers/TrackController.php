<?php

namespace App\Modules\Analytics\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Analytics\Models\SitePageView;
use App\Modules\Analytics\Models\SiteVisit;
use App\Modules\Analytics\Services\GeoLocator;
use App\Modules\Analytics\Services\UserAgentParser;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;
use Throwable;

/**
 * Public, unauthenticated beacon endpoint used by the SPA.
 *
 * Always answers 204 (or 422 on malformed input) so the client never has
 * anything to react to; tracking must be invisible to visitors.
 */
class TrackController extends Controller
{
    /** Sessions longer than this are treated as a tab left open, not real engagement. */
    public const MAX_DURATION_SECONDS = 4 * 3600;

    /** A single-page session still counts as engaged after this long. */
    public const ENGAGED_AFTER_SECONDS = 10;

    public function __construct(
        private readonly UserAgentParser $agents,
        private readonly GeoLocator $geo,
    ) {
    }

    public function track(Request $request): Response
    {
        $userAgent = (string) $request->userAgent();
        if ($this->agents->isBot($userAgent)) {
            return response()->noContent();
        }

        $validator = Validator::make($this->payload($request), [
            'visitor_id' => ['required', 'string', 'max:64', 'regex:/^[A-Za-z0-9_-]+$/'],
            'session_id' => ['required', 'string', 'max:64', 'regex:/^[A-Za-z0-9_-]+$/'],
            'path' => ['required', 'string', 'max:2000'],
            'referrer' => ['nullable', 'string', 'max:2000'],
            'event' => ['required', 'in:pageview,heartbeat'],
        ]);

        if ($validator->fails()) {
            return response()->noContent(422);
        }

        $data = $validator->validated();
        $user = $this->currentUser();

        // Admins would skew the numbers they are looking at.
        if ($user && method_exists($user, 'hasRole') && $this->safeHasRole($user, 'admin')) {
            return response()->noContent();
        }

        $path = $this->cleanPath($data['path']);
        $now = Carbon::now();

        $visit = SiteVisit::where('session_id', $data['session_id'])->first();

        if ($data['event'] === 'heartbeat') {
            if ($visit) {
                $this->touch($visit, $now, $user?->getKey());
                $visit->save();
            }

            return response()->noContent();
        }

        if (! $visit) {
            $visit = $this->createVisit($request, $data, $path, $userAgent, $now, $user?->getKey());
            if ($visit->wasRecentlyCreated) {
                $this->logPageView($visit->session_id, $path, $now);

                return response()->noContent();
            }
        }

        $visit->page_views = $visit->page_views + 1;
        $visit->exit_path = $path;
        $this->touch($visit, $now, $user?->getKey());
        $visit->save();

        $this->logPageView($visit->session_id, $path, $now);

        return response()->noContent();
    }

    /**
     * navigator.sendBeacon posts a text/plain body, so JSON is decoded by hand.
     */
    private function payload(Request $request): array
    {
        $data = $request->all();
        if (! empty($data)) {
            return $data;
        }

        $decoded = json_decode((string) $request->getContent(), true);

        return is_array($decoded) ? $decoded : [];
    }

    private function currentUser()
    {
        try {
            return auth('sanctum')->user();
        } catch (Throwable) {
            return null;
        }
    }

    private function safeHasRole($user, string $role): bool
    {
        try {
            return $user->hasRole($role);
        } catch (Throwable) {
            return false;
        }
    }

    private function createVisit(Request $request, array $data, string $path, string $userAgent, Carbon $now, $userId): SiteVisit
    {
        $ip = $this->clientIp($request);
        $ipHash = hash('sha256', ($ip ?? '') . '|' . config('app.key'));
        $device = $this->agents->parse($userAgent);
        $geo = $this->geo->locate($request, $ip, $ipHash);

        try {
            return SiteVisit::create([
                'visitor_id' => $data['visitor_id'],
                'session_id' => $data['session_id'],
                'user_id' => $userId,
                'ip_hash' => $ipHash,
                'country_code' => $geo['country_code'],
                'country_name' => $geo['country_name'],
                'city' => $geo['city'],
                'device_type' => $device['device_type'],
                'browser' => $device['browser'],
                'os' => $device['os'],
                'referrer' => $this->cleanReferrer($data['referrer'] ?? null, $request),
                'landing_path' => $path,
                'exit_path' => $path,
                'page_views' => 1,
                'started_at' => $now,
                'last_seen_at' => $now,
                'duration_seconds' => 0,
                'is_bounce' => true,
            ]);
        } catch (QueryException $e) {
            // Two tabs raced to open the same session: fall back to the row that won.
            $existing = SiteVisit::where('session_id', $data['session_id'])->first();
            if ($existing) {
                return $existing;
            }
            throw $e;
        }
    }

    private function touch(SiteVisit $visit, Carbon $now, $userId): void
    {
        $visit->last_seen_at = $now;
        $visit->duration_seconds = (int) min(
            self::MAX_DURATION_SECONDS,
            max(0, $now->getTimestamp() - $visit->started_at->getTimestamp())
        );

        if ($visit->page_views > 1 || $visit->duration_seconds >= self::ENGAGED_AFTER_SECONDS) {
            $visit->is_bounce = false;
        }

        if ($userId && ! $visit->user_id) {
            $visit->user_id = $userId;
        }
    }

    private function logPageView(string $sessionId, string $path, Carbon $now): void
    {
        try {
            SitePageView::create(['session_id' => $sessionId, 'path' => $path, 'viewed_at' => $now]);
        } catch (Throwable) {
            // Top-pages stats are best effort.
        }
    }

    private function clientIp(Request $request): ?string
    {
        $cf = $request->headers->get('CF-Connecting-IP');
        if ($cf && filter_var($cf, FILTER_VALIDATE_IP)) {
            return $cf;
        }

        return $request->ip();
    }

    /**
     * Keep only the pathname: query strings can carry tokens (password resets, etc.).
     */
    private function cleanPath(string $path): string
    {
        $path = parse_url($path, PHP_URL_PATH) ?: '/';
        if ($path[0] !== '/') {
            $path = '/' . $path;
        }

        return mb_substr($path, 0, 500);
    }

    /**
     * Stores origin + path of external referrers only; internal navigation is not a referrer.
     */
    private function cleanReferrer(?string $referrer, Request $request): ?string
    {
        if (! $referrer) {
            return null;
        }

        $parts = parse_url($referrer);
        if (! $parts || empty($parts['host'])) {
            return null;
        }

        $host = strtolower($parts['host']);
        $origin = parse_url((string) $request->headers->get('Origin'), PHP_URL_HOST);
        if ($origin && strtolower($origin) === $host) {
            return null;
        }

        $clean = ($parts['scheme'] ?? 'https') . '://' . $host . ($parts['path'] ?? '');

        return mb_substr($clean, 0, 500);
    }
}
