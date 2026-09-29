<?php

namespace App\Modules\Billing\database\seeders;

use App\Modules\Billing\Services\FreePlanService;
use Illuminate\Database\Seeder;

/** The free plan every store is put on while billing is not live. */
class FreePlanSeeder extends Seeder
{
    public function run(FreePlanService $freePlan): void
    {
        $freePlan->plan();
    }
}
