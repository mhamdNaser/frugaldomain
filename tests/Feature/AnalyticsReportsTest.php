<?php

use App\Modules\Analytics\Models\SitePageView;
use App\Modules\Analytics\Models\SiteVisit;
use App\Modules\User\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;

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

    Carbon::setTestNow(Carbon::parse('2026-09-29 12:00:00'));

    Role::findOrCreate('admin', 'web');
    Role::findOrCreate('partner', 'web');

    $this->admin = User::create([
        'first_name' => 'Ad', 'last_name' => 'Min', 'name' => 'admin',
        'email' => 'admin@test.com', 'password' => 'x', 'status' => 1,
    ]);
    $this->admin->assignRole('admin');

    $this->member = User::create([
        'first_name' => 'Sara', 'last_name' => 'Ali', 'name' => 'sara',
        'email' => 'sara@test.com', 'password' => 'x', 'status' => 1,
    ]);
});

afterEach(fn () => Carbon::setTestNow());

function makeVisit(array $attrs = []): SiteVisit
{
    static $n = 0;
    $n++;
    $start = $attrs['started_at'] ?? now()->subHour();

    return SiteVisit::create(array_merge([
        'visitor_id' => "v$n",
        'session_id' => "s$n",
        'ip_hash' => str_repeat('a', 64),
        'country_code' => 'DE',
        'country_name' => 'Germany',
        'city' => 'Berlin',
        'device_type' => 'desktop',
        'browser' => 'Chrome',
        'os' => 'Windows',
        'landing_path' => '/',
        'exit_path' => '/',
        'page_views' => 1,
        'started_at' => $start,
        'last_seen_at' => $start,
        'duration_seconds' => 0,
        'is_bounce' => true,
    ], $attrs));
}

it('is only available to admins', function () {
    $this->getJson('/api/admin/analytics/overview')->assertUnauthorized();

    $this->member->assignRole('partner');
    Sanctum::actingAs($this->member);
    $this->getJson('/api/admin/analytics/overview')->assertForbidden();
});

it('returns overview totals with change against the previous period', function () {
    // current 7d window: 3 sessions from 2 visitors
    makeVisit(['visitor_id' => 'a', 'page_views' => 3, 'duration_seconds' => 120, 'is_bounce' => false, 'user_id' => $this->member->id]);
    makeVisit(['visitor_id' => 'a', 'started_at' => now()->subDays(2), 'duration_seconds' => 60, 'is_bounce' => false, 'device_type' => 'mobile']);
    makeVisit(['visitor_id' => 'b', 'started_at' => now()->subDays(3), 'last_seen_at' => now()->subMinute()]);
    // previous 7d window: 1 session
    makeVisit(['visitor_id' => 'c', 'started_at' => now()->subDays(10)]);
    // outside both windows
    makeVisit(['visitor_id' => 'd', 'started_at' => now()->subDays(40)]);

    Sanctum::actingAs($this->admin);
    $res = $this->getJson('/api/admin/analytics/overview?range=7d')->assertOk();

    $res->assertJsonPath('data.range', '7d')
        ->assertJsonPath('data.current.visitors', 2)
        ->assertJsonPath('data.current.sessions', 3)
        ->assertJsonPath('data.current.page_views', 5)
        ->assertJsonPath('data.current.avg_session_seconds', 60)
        ->assertJsonPath('data.current.registered_sessions', 1)
        ->assertJsonPath('data.current.anonymous_sessions', 2)
        ->assertJsonPath('data.previous.sessions', 1)
        ->assertJsonPath('data.change.sessions', 200)
        ->assertJsonPath('data.online_now', 1);

    expect($res->json('data.current.bounce_rate'))->toEqual(33.3);
    expect(collect($res->json('data.devices'))->pluck('sessions', 'device_type')->all())
        ->toBe(['desktop' => 2, 'mobile' => 1]);
});

it('buckets the timeseries by day, week and month with empty buckets filled', function () {
    makeVisit(['visitor_id' => 'a', 'started_at' => now()->subDays(1)]);
    makeVisit(['visitor_id' => 'a', 'started_at' => now()->subDays(1)->addHour(), 'page_views' => 4]);
    makeVisit(['visitor_id' => 'b', 'started_at' => now()->subDays(3)]);

    Sanctum::actingAs($this->admin);

    $daily = $this->getJson('/api/admin/analytics/timeseries?range=7d&interval=day')->assertOk()->json('data.series');
    expect($daily)->toHaveCount(7);
    $byDate = collect($daily)->keyBy('date');
    expect($byDate['2026-09-28'])->toBe(['date' => '2026-09-28', 'visitors' => 1, 'sessions' => 2, 'page_views' => 5])
        ->and($byDate['2026-09-26']['visitors'])->toBe(1)
        ->and($byDate['2026-09-29']['sessions'])->toBe(0);

    $weekly = $this->getJson('/api/admin/analytics/timeseries?range=30d&interval=week')->assertOk()->json('data.series');
    $lastWeek = collect($weekly)->firstWhere('date', '2026-09-28'); // Monday
    $prevWeek = collect($weekly)->firstWhere('date', '2026-09-21');
    expect($lastWeek['sessions'])->toBe(2)
        ->and($prevWeek['sessions'])->toBe(1);

    $monthly = $this->getJson('/api/admin/analytics/timeseries?range=12m&interval=month')->assertOk()->json('data.series');
    expect($monthly)->toHaveCount(12)
        ->and(end($monthly))->toBe(['date' => '2026-09-01', 'visitors' => 2, 'sessions' => 3, 'page_views' => 6]);
});

it('ranks countries with share', function () {
    makeVisit(['visitor_id' => 'a']);
    makeVisit(['visitor_id' => 'b']);
    makeVisit(['visitor_id' => 'c', 'country_code' => 'SA', 'country_name' => 'Saudi Arabia', 'duration_seconds' => 90]);
    makeVisit(['visitor_id' => 'd', 'country_code' => null, 'country_name' => null]);

    Sanctum::actingAs($this->admin);
    $res = $this->getJson('/api/admin/analytics/countries')->assertOk();

    $res->assertJsonPath('data.total_visitors', 4)
        ->assertJsonPath('data.countries.0.country_code', 'DE')
        ->assertJsonPath('data.countries.0.visitors', 2);
    expect($res->json('data.countries.0.share'))->toEqual(50);
    expect(collect($res->json('data.countries'))->firstWhere('country_code', 'SA')['avg_session_seconds'])->toBe(90);
});

it('lists recent visitors with the user when known', function () {
    makeVisit(['user_id' => $this->member->id, 'started_at' => now()->subMinutes(5)]);
    makeVisit(['started_at' => now()->subMinutes(10)]);
    foreach (range(1, 20) as $i) {
        makeVisit(['started_at' => now()->subDays(1)]);
    }

    Sanctum::actingAs($this->admin);
    $res = $this->getJson('/api/admin/analytics/visitors')->assertOk();

    $res->assertJsonPath('meta.total', 22)
        ->assertJsonPath('meta.last_page', 2)
        ->assertJsonPath('data.0.user.email', 'sara@test.com')
        ->assertJsonPath('data.0.user.name', 'Sara Ali')
        ->assertJsonPath('data.1.user', null);
    expect($res->json('data'))->toHaveCount(20);
});

it('returns top and landing pages', function () {
    $v = makeVisit(['landing_path' => '/pricing']);
    makeVisit(['landing_path' => '/pricing', 'is_bounce' => false]);
    makeVisit(['landing_path' => '/']);
    foreach (['/pricing', '/pricing', '/about'] as $path) {
        SitePageView::create(['session_id' => $v->session_id, 'path' => $path, 'viewed_at' => now()->subMinutes(30)]);
    }

    Sanctum::actingAs($this->admin);
    $res = $this->getJson('/api/admin/analytics/pages')->assertOk();

    $res->assertJsonPath('data.top_pages.0.path', '/pricing')
        ->assertJsonPath('data.top_pages.0.views', 2)
        ->assertJsonPath('data.top_pages.0.sessions', 1)
        ->assertJsonPath('data.landing_pages.0.path', '/pricing')
        ->assertJsonPath('data.landing_pages.0.sessions', 2);
    expect($res->json('data.landing_pages.0.bounce_rate'))->toEqual(50);
});
