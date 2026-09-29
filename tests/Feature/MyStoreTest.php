<?php

use App\Modules\Billing\Models\Subscription;
use App\Modules\Stores\Models\Store;
use App\Modules\User\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Spatie\Permission\Models\Role;

// The full migration set is MySQL-only, so load just the tables this flow touches.
beforeEach(function () {
    $m = fn ($module, $file) => base_path("app/Modules/{$module}/database/migrations/{$file}.php");
    Artisan::call('migrate', ['--realpath' => true, '--path' => [
        $m('Locale', '0001_01_01_000003_create_countries_table'),
        $m('Locale', '0001_01_01_000004_create_states_table'),
        $m('Locale', '0001_01_01_000005_create_cities_table'),
        $m('User', '0001_01_01_000006_create_users_table'),
        $m('User', '2026_09_29_120000_allow_self_registration_on_users_table'),
        $m('User', '2025_10_04_075133_create_personal_access_tokens_table'),
        $m('User', '2025_10_11_123012_create_permission_tables'),
        $m('Billing', '2026_02_26_091148_create_plans_table'),
        $m('Stores', '2026_02_26_091149_create_stores_table'),
        $m('Stores', '2026_04_24_131615_add_shopify_webhook_secret_to_stores_table'),
        $m('Billing', '2026_02_26_091421_create_subscriptions_table'),
        $m('Core', '2026_03_26_105253_create_sync_runs_table'),
    ]]);
    Role::findOrCreate('partner', 'web');
    Role::findOrCreate('admin', 'web');

    $this->owner = User::create([
        'first_name' => 'Store', 'last_name' => 'Owner', 'name' => 'owner',
        'email' => 'owner@test.com', 'password' => 'x', 'status' => 1,
    ]);
    $this->owner->assignRole('partner');
});

function fakeShopify(array $scopes = ['read_products', 'write_inventory', 'read_locations', 'read_orders', 'read_customers'], int $status = 200): void
{
    Http::fake([
        '*/admin/api/*/shop.json' => Http::response($status === 200 ? ['shop' => [
            'id' => 85380268197,
            'name' => 'Test Shop',
            'email' => 'shop@test.com',
            'myshopify_domain' => 'test-shop.myshopify.com',
            'currency' => 'USD',
            'iana_timezone' => 'America/New_York',
            'plan_name' => 'basic',
        ]] : ['errors' => 'Invalid API key or access token'], $status),
        '*/admin/oauth/access_scopes.json' => Http::response([
            'access_scopes' => array_map(fn ($s) => ['handle' => $s], $scopes),
        ]),
    ]);
}

it('verifies credentials without saving anything', function () {
    fakeShopify();

    $this->actingAs($this->owner)->postJson('/api/admin/my-store/verify', [
        'shopify_domain' => 'https://Test-Shop.myshopify.com/admin',
        'shopify_access_token' => 'shpat_test',
    ])->assertOk()
        ->assertJsonPath('data.shop.name', 'Test Shop')
        ->assertJsonPath('data.missing_required', []);

    expect(Store::count())->toBe(0);
    Http::assertSent(fn ($r) => str_starts_with($r->url(), 'https://test-shop.myshopify.com/'));
});

it('rejects a domain that is not a myshopify.com domain before calling anyone', function () {
    Http::fake();

    $this->actingAs($this->owner)->postJson('/api/admin/my-store/verify', [
        'shopify_domain' => 'evil.example.com',
        'shopify_access_token' => 'shpat_test',
    ])->assertStatus(422);

    Http::assertNothingSent();
});

it('explains a rejected token', function () {
    fakeShopify(status: 401);

    $this->actingAs($this->owner)->postJson('/api/admin/my-store/verify', [
        'shopify_domain' => 'test-shop.myshopify.com',
        'shopify_access_token' => 'shpss_wrong_kind',
    ])->assertStatus(422)->assertJsonPath('message', fn ($m) => str_contains($m, 'shpat_'));
});

it('connects the store with Shopify details, encrypts the token and puts it on the free plan', function () {
    fakeShopify();

    $this->actingAs($this->owner)->postJson('/api/admin/my-store/connect', [
        'shopify_domain' => 'test-shop.myshopify.com',
        'shopify_access_token' => 'shpat_secret',
        'shopify_webhook_secret' => 'shpss_secret',
    ])->assertCreated()
        ->assertJsonPath('data.name', 'Test Shop')
        ->assertJsonPath('data.plan.name', 'Free')
        ->assertJsonPath('data.has_access_token', true)
        ->assertJsonMissingPath('data.shopify_access_token');

    $store = Store::firstOrFail();
    expect($store->owner_id)->toBe($this->owner->id)
        ->and($store->shopify_store_id)->toBe(85380268197)
        ->and($store->shopify_access_token)->not->toBe('shpat_secret')
        ->and(Crypt::decryptString($store->shopify_access_token))->toBe('shpat_secret')
        ->and(Subscription::where('store_id', $store->id)->where('status', 'active')->count())->toBe(1);

    // Reconnecting keeps the saved token and does not add a second subscription.
    $this->actingAs($this->owner)->postJson('/api/admin/my-store/connect', [
        'shopify_domain' => 'test-shop.myshopify.com',
    ])->assertOk();
    expect(Store::count())->toBe(1)
        ->and(Subscription::count())->toBe(1);
});

it('refuses a token without the scopes the import needs', function () {
    fakeShopify(scopes: ['read_products']);

    $this->actingAs($this->owner)->postJson('/api/admin/my-store/connect', [
        'shopify_domain' => 'test-shop.myshopify.com',
        'shopify_access_token' => 'shpat_secret',
    ])->assertStatus(422)->assertJsonPath('data.missing_required', ['read_inventory', 'read_locations', 'read_orders', 'read_customers']);

    expect(Store::count())->toBe(0);
});

it('does not let a second account take a store that is already connected', function () {
    fakeShopify();
    $this->actingAs($this->owner)->postJson('/api/admin/my-store/connect', [
        'shopify_domain' => 'test-shop.myshopify.com', 'shopify_access_token' => 'shpat_secret',
    ])->assertCreated();

    $other = User::create([
        'first_name' => 'O', 'last_name' => 'T', 'name' => 'other',
        'email' => 'other@test.com', 'password' => 'x', 'status' => 1,
    ]);
    $other->assignRole('partner');

    $this->actingAs($other)->postJson('/api/admin/my-store/connect', [
        'shopify_domain' => 'test-shop.myshopify.com', 'shopify_access_token' => 'shpat_other',
    ])->assertStatus(422);
});

it('keeps the my-store endpoints away from super admins and guests', function () {
    $admin = User::create([
        'first_name' => 'A', 'last_name' => 'D', 'name' => 'admin',
        'email' => 'admin@test.com', 'password' => 'x', 'status' => 1,
    ]);
    $admin->assignRole('admin');

    $this->getJson('/api/admin/my-store')->assertUnauthorized();
    $this->actingAs($admin)->getJson('/api/admin/my-store')->assertForbidden();
    $this->actingAs($this->owner)->getJson('/api/admin/my-store')->assertOk()->assertJsonPath('data', null);
});

it('lets the super admin add a store for a store owner using only the Shopify credentials', function () {
    fakeShopify();
    $admin = User::create([
        'first_name' => 'A', 'last_name' => 'D', 'name' => 'admin',
        'email' => 'admin@test.com', 'password' => 'x', 'status' => 1,
    ]);
    $admin->assignRole('admin');

    // Owner must be a store owner...
    $this->actingAs($admin)->postJson('/api/admin/store', [
        'owner_id' => $admin->id,
        'shopify_domain' => 'test-shop.myshopify.com',
        'shopify_access_token' => 'shpat_secret',
    ])->assertStatus(422)->assertJsonValidationErrors(['owner_id']);

    $this->actingAs($admin)->postJson('/api/admin/store', [
        'owner_id' => $this->owner->id,
        'shopify_domain' => 'test-shop.myshopify.com',
        'shopify_access_token' => 'shpat_secret',
    ])->assertCreated();

    $store = Store::firstOrFail();
    expect($store->owner_id)->toBe($this->owner->id)
        ->and($store->name)->toBe('Test Shop')
        ->and($store->currency)->toBe('USD')
        ->and(Subscription::where('store_id', $store->id)->count())->toBe(1);

    // ...and only one store per owner; the owner list for the form excludes them now.
    $this->actingAs($admin)->postJson('/api/admin/store', [
        'owner_id' => $this->owner->id,
        'shopify_domain' => 'test-shop.myshopify.com',
        'shopify_access_token' => 'shpat_secret',
    ])->assertStatus(422)->assertJsonValidationErrors(['owner_id']);

    $this->actingAs($admin)->getJson('/api/admin/all-users?store_owners=1')->assertOk()->assertJsonCount(0);
});
