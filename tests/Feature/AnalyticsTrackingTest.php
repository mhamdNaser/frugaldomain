<?php

use App\Modules\Analytics\Models\SitePageView;
use App\Modules\Analytics\Models\SiteVisit;
use App\Modules\User\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
use Spatie\Permission\Models\Role;

const ANALYTICS_UA = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/128.0 Safari/537.36';

beforeEach(function () {
    $user = base_path('app/Modules/User/database/migrations');
    $locale = base_path('app/Modules/Locale/database/migrations');
    $analytics = base_path('app/Modules/Analytics/database/migrations');
    Artisan::call('migrate', ['--realpath' => true, '--path' => [
        "$locale/0001_01_01_000003_create_countries_table.php",
        "$locale/0001_01_01_000004_create_states_table.php",
        "$locale/0001_01_01_000005_create_cities_table.php",
        "$user/0001_01_01_000006_create_users_table.php",
        "$user/2026_09_29_120000_allow_self_registration_on_users_table.php",
        "$user/2025_10_04_075133_create_personal_access_tokens_table.php",
        "$user/2025_10_11_123012_create_permission_tables.php",
        base_path('app/Modules/Stores/database/migrations/2026_02_26_091149_create_stores_table.php'),
        "$analytics/2026_09_29_200000_create_site_visits_table.php",
        "$analytics/2026_09_29_200001_create_site_page_views_table.php",
    ]]);

    // Tests swap $this->geoResponse to simulate ip-api outages.
    $this->geoResponse = fn () => Http::response([
        'status' => 'success', 'country' => 'Germany', 'countryCode' => 'DE', 'city' => 'Berlin',
    ]);
    Http::fake(['ip-api.com/*' => fn () => ($this->geoResponse)()]);
});

function trackEvent($test, array $overrides = [], array $headers = [])
{
    return $test->withHeaders(['User-Agent' => ANALYTICS_UA] + $headers)
        ->withServerVariables(['REMOTE_ADDR' => '8.8.8.8'])
        ->postJson('/api/analytics/track', array_merge([
            'visitor_id' => 'visitor-1',
            'session_id' => 'session-1',
            'path' => '/pricing?token=secret',
            'referrer' => 'https://www.google.com/search?q=frugal',
            'event' => 'pageview',
        ], $overrides));
}

it('creates a session on the first pageview with geo, device and a hashed ip', function () {
    trackEvent($this)->assertNoContent();

    $visit = SiteVisit::firstOrFail();
    expect($visit->visitor_id)->toBe('visitor-1')
        ->and($visit->landing_path)->toBe('/pricing')
        ->and($visit->referrer)->toBe('https://www.google.com/search')
        ->and($visit->country_code)->toBe('DE')
        ->and($visit->country_name)->toBe('Germany')
        ->and($visit->city)->toBe('Berlin')
        ->and($visit->device_type)->toBe('desktop')
        ->and($visit->browser)->toBe('Chrome')
        ->and($visit->os)->toBe('Windows')
        ->and($visit->page_views)->toBe(1)
        ->and($visit->is_bounce)->toBeTrue()
        ->and($visit->ip_hash)->toHaveLength(64)
        ->and($visit->ip_hash)->not->toContain('8.8.8.8')
        ->and($visit->user_id)->toBeNull();

    expect(SitePageView::count())->toBe(1);
    Http::assertSentCount(1);
});

it('prefers CDN country headers over the ip lookup', function () {
    trackEvent($this, [], ['CF-IPCountry' => 'sa'])->assertNoContent();

    expect(SiteVisit::first()->country_code)->toBe('SA');
    Http::assertNothingSent();
});

it('caches ip lookups and skips private addresses', function () {
    trackEvent($this);
    trackEvent($this, ['session_id' => 'session-2']);
    Http::assertSentCount(1);

    $this->withHeaders(['User-Agent' => ANALYTICS_UA])
        ->withServerVariables(['REMOTE_ADDR' => '192.168.1.10'])
        ->postJson('/api/analytics/track', [
            'visitor_id' => 'v-local', 'session_id' => 's-local', 'path' => '/', 'event' => 'pageview',
        ])->assertNoContent();

    Http::assertSentCount(1);
    expect(SiteVisit::where('session_id', 's-local')->first()->country_code)->toBeNull();
});

it('survives a failing geo service', function () {
    $this->geoResponse = fn () => Http::response(null, 500);

    trackEvent($this)->assertNoContent();
    expect(SiteVisit::first()->country_code)->toBeNull();
});

it('counts further pageviews and marks the session as engaged', function () {
    trackEvent($this);
    trackEvent($this, ['path' => '/about']);

    $visit = SiteVisit::first();
    expect($visit->page_views)->toBe(2)
        ->and($visit->exit_path)->toBe('/about')
        ->and($visit->landing_path)->toBe('/pricing')
        ->and($visit->is_bounce)->toBeFalse();
    expect(SitePageView::count())->toBe(2);
});

it('updates duration on heartbeat and caps it', function () {
    trackEvent($this);

    $this->travel(30)->seconds();
    trackEvent($this, ['event' => 'heartbeat'])->assertNoContent();

    $visit = SiteVisit::first();
    expect($visit->duration_seconds)->toBeGreaterThanOrEqual(30)
        ->and($visit->is_bounce)->toBeFalse()
        ->and($visit->page_views)->toBe(1);

    $this->travel(10)->hours();
    trackEvent($this, ['event' => 'heartbeat']);
    expect(SiteVisit::first()->duration_seconds)->toBe(4 * 3600);
});

it('ignores heartbeats for unknown sessions', function () {
    trackEvent($this, ['event' => 'heartbeat'])->assertNoContent();
    expect(SiteVisit::count())->toBe(0);
});

it('ignores bots', function () {
    $this->withHeaders(['User-Agent' => 'Mozilla/5.0 (compatible; Googlebot/2.1)'])
        ->postJson('/api/analytics/track', [
            'visitor_id' => 'bot', 'session_id' => 'bot', 'path' => '/', 'event' => 'pageview',
        ])->assertNoContent();

    expect(SiteVisit::count())->toBe(0);
});

it('rejects malformed payloads', function () {
    trackEvent($this, ['event' => 'click'])->assertStatus(422);
    trackEvent($this, ['session_id' => 'bad id; drop'])->assertStatus(422);
    expect(SiteVisit::count())->toBe(0);
});

it('accepts a text/plain beacon body', function () {
    $this->withHeaders(['User-Agent' => ANALYTICS_UA, 'Content-Type' => 'text/plain'])
        ->call('POST', '/api/analytics/track', [], [], [], ['CONTENT_TYPE' => 'text/plain', 'HTTP_USER_AGENT' => ANALYTICS_UA], json_encode([
            'visitor_id' => 'v-b', 'session_id' => 's-b', 'path' => '/', 'event' => 'pageview',
        ]))->assertNoContent();

    expect(SiteVisit::where('session_id', 's-b')->exists())->toBeTrue();
});

it('records the logged-in user from a sanctum token but skips admins', function () {
    $partner = User::create([
        'first_name' => 'Sara', 'last_name' => 'Ali', 'name' => 'sara',
        'email' => 'sara@test.com', 'password' => 'x', 'status' => 1,
    ]);
    $token = $partner->createToken('t')->plainTextToken;

    trackEvent($this, [], ['Authorization' => "Bearer $token"])->assertNoContent();
    expect(SiteVisit::first()->user_id)->toBe($partner->id);

    Role::findOrCreate('admin', 'web');
    $admin = User::create([
        'first_name' => 'Ad', 'last_name' => 'Min', 'name' => 'admin',
        'email' => 'admin@test.com', 'password' => 'x', 'status' => 1,
    ]);
    $admin->assignRole('admin');
    $adminToken = $admin->createToken('t')->plainTextToken;

    app('auth')->forgetGuards(); // guards are memoised across requests inside one test

    trackEvent($this, ['session_id' => 'admin-session'], ['Authorization' => "Bearer $adminToken"])->assertNoContent();
    expect(SiteVisit::where('session_id', 'admin-session')->exists())->toBeFalse();
});

it('parses mobile and tablet user agents', function () {
    $parser = new App\Modules\Analytics\Services\UserAgentParser();

    expect($parser->parse('Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15 Version/17.0 Mobile/15E148 Safari/604.1'))
        ->toBe(['device_type' => 'mobile', 'browser' => 'Safari', 'os' => 'iOS'])
        ->and($parser->parse('Mozilla/5.0 (iPad; CPU OS 16_0 like Mac OS X) AppleWebKit/605.1.15 CriOS/120 Safari/604.1'))
        ->toBe(['device_type' => 'tablet', 'browser' => 'Chrome', 'os' => 'iOS'])
        ->and($parser->parse('Mozilla/5.0 (Linux; Android 14; SM-S918B) AppleWebKit/537.36 Chrome/120 Mobile Safari/537.36 EdgA/120'))
        ->toBe(['device_type' => 'mobile', 'browser' => 'Edge', 'os' => 'Android'])
        ->and($parser->isBot('HeadlessChrome/120'))->toBeTrue();
});
