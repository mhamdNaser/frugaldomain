<?php

namespace App\Modules\User\database\seeders;

use App\Modules\Stores\Models\Store;
use App\Modules\User\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;

/**
 * Two ready-to-use accounts for trying Verdia end to end:
 *
 *  - a super admin (role "admin"), who signs in at /admin/login and sees the
 *    whole platform;
 *  - a Shopify store owner (role "partner") with a store attached, who signs
 *    in at /login and lands on the merchant dashboard.
 *
 * Safe to run more than once: accounts are matched by email and updated.
 *
 * Optional overrides in .env:
 *   SEED_ADMIN_PASSWORD, SEED_PARTNER_PASSWORD
 *   SEED_SHOPIFY_DOMAIN  e.g. my-store.myshopify.com
 *   SEED_SHOPIFY_TOKEN   Admin API access token (shpat_...), stored encrypted
 */
class VerdiaDemoSeeder extends Seeder
{
    public function run(): void
    {
        $admin = $this->upsertUser([
            'first_name' => 'Verdia',
            'medium_name' => 'Super',
            'last_name' => 'Admin',
            'name' => 'verdia_admin',
            'email' => 'admin@collectify.sbs',
            'phone' => 962790000001,
            'password' => env('SEED_ADMIN_PASSWORD', 'Verdia@Admin2026'),
        ]);
        $admin->syncRoles(['admin']);

        $owner = $this->upsertUser([
            'first_name' => 'Noor',
            'medium_name' => 'Store',
            'last_name' => 'Owner',
            'name' => 'verdia_store_owner',
            'email' => 'owner@collectify.sbs',
            'phone' => 962790000002,
            'password' => env('SEED_PARTNER_PASSWORD', 'Verdia@Store2026'),
        ]);
        $owner->syncRoles(['partner']);

        $token = env('SEED_SHOPIFY_TOKEN');

        $store = Store::withTrashed()->updateOrCreate(
            ['owner_id' => $owner->id],
            [
                'name' => 'Atelier Noor',
                'email' => $owner->email,
                'shopify_domain' => env('SEED_SHOPIFY_DOMAIN', 'atelier-noor-demo.myshopify.com'),
                'shopify_access_token' => $token ? Crypt::encryptString($token) : null,
                'currency' => 'USD',
                'timezone' => 'Asia/Amman',
                'status' => 'active',
                'installed_at' => now(),
            ]
        );
        if ($store->trashed()) {
            $store->restore();
        }

        $this->command?->info('Verdia demo accounts are ready:');
        $this->command?->table(
            ['Role', 'Sign in at', 'Email'],
            [
                ['Super admin', '/admin/login', $admin->email],
                ['Store owner', '/login', $owner->email],
            ]
        );
        $this->command?->line(
            'Store: ' . $store->shopify_domain . ($token
                ? ' (access token saved, encrypted)'
                : ' (no access token yet - set SEED_SHOPIFY_TOKEN to connect a real store)')
        );
    }

    private function upsertUser(array $data): User
    {
        $password = $data['password'];
        unset($data['password']);

        $user = User::firstOrNew(['email' => $data['email']]);
        $user->fill($data + ['status' => 1]);
        $user->password = Hash::make($password);
        // is_active is not mass assignable, so it is set explicitly.
        $user->forceFill(['is_active' => 1]);
        $user->save();

        return $user;
    }
}
