<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Modules\Locale\database\seeders\LanguageSeeder;
use App\Modules\User\database\seeders\AdminSeeder;
use App\Modules\User\database\seeders\RolePermissionSeeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RolePermissionSeeder::class,
            AdminSeeder::class,
            LanguageSeeder::class,
        ]);
    }
}
