<?php

use App\Modules\User\Models\User;
use Illuminate\Support\Facades\Artisan;
use Spatie\Permission\Models\Role;

// The full migration set is MySQL-only, so load just the tables sign-up touches.
beforeEach(function () {
    $dir = base_path('app/Modules/User/database/migrations');
    $locale = base_path('app/Modules/Locale/database/migrations');
    Artisan::call('migrate', ['--realpath' => true, '--path' => [
        "$locale/0001_01_01_000003_create_countries_table.php",
        "$locale/0001_01_01_000004_create_states_table.php",
        "$locale/0001_01_01_000005_create_cities_table.php",
        "$dir/0001_01_01_000006_create_users_table.php",
        "$dir/2026_09_29_120000_allow_self_registration_on_users_table.php",
        "$dir/2025_10_04_075133_create_personal_access_tokens_table.php",
        "$dir/2025_10_11_123012_create_permission_tables.php",
        base_path('app/Modules/Stores/database/migrations/2026_02_26_091149_create_stores_table.php'),
    ]]);
    Role::findOrCreate('partner', 'web');
});

it('creates a partner account from the public sign-up form', function () {
    $res = $this->postJson('/api/admin/adminregister', [
        'name' => 'Naser Nasser Edden',
        'email' => 'naser@test.com',
        'password' => 'Secret123',
        'password_confirmation' => 'Secret123',
    ]);

    $res->assertCreated()->assertJsonStructure(['token', 'user']);

    $user = User::where('email', 'naser@test.com')->firstOrFail();
    expect($user->first_name)->toBe('Naser')
        ->and($user->last_name)->toBe('Nasser Edden')
        ->and($user->name)->toBe('naser')
        ->and($user->hasRole('partner'))->toBeTrue();
});

it('gives each account a unique username', function () {
    foreach (['naser@test.com', 'naser@other.com'] as $email) {
        $this->postJson('/api/admin/adminregister', [
            'name' => 'Naser',
            'email' => $email,
            'password' => 'Secret123',
            'password_confirmation' => 'Secret123',
        ])->assertCreated();
    }

    expect(User::pluck('name')->all())->toBe(['naser', 'naser_2']);
});

it('rejects a duplicate email, a short or unconfirmed password, and ignores a requested role', function () {
    User::create([
        'first_name' => 'A', 'last_name' => 'B', 'name' => 'taken',
        'email' => 'taken@test.com', 'password' => 'x', 'status' => 1,
    ]);

    $this->postJson('/api/admin/adminregister', [
        'name' => 'X', 'email' => 'taken@test.com',
        'password' => 'short', 'password_confirmation' => 'other',
    ])->assertStatus(422)->assertJsonValidationErrors(['email', 'password']);

    $this->postJson('/api/admin/adminregister', [
        'name' => 'Sneaky', 'email' => 'sneaky@test.com', 'role' => 'admin',
        'password' => 'Secret123', 'password_confirmation' => 'Secret123',
    ])->assertCreated();

    expect(User::where('email', 'sneaky@test.com')->first()->getRoleNames()->all())->toBe(['partner']);
});
