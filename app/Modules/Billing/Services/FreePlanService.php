<?php

namespace App\Modules\Billing\Services;

use App\Modules\Billing\Models\Plan;
use App\Modules\Billing\Models\Subscription;
use App\Modules\Stores\Models\Store;

/**
 * While billing is not live every store runs on one free plan. New stores are
 * put on it automatically when their owner connects them.
 */
class FreePlanService
{
    public const PLAN_NAME = 'Free';

    public function plan(): Plan
    {
        $plan = Plan::withTrashed()->firstOrCreate(
            ['name' => self::PLAN_NAME, 'billing_interval' => 'month'],
            ['price' => 0, 'trial_days' => null, 'is_active' => true],
        );

        if ($plan->trashed()) {
            $plan->restore();
        }

        return $plan;
    }

    /** Gives the store the free plan unless it already has an active subscription. */
    public function subscribe(Store $store): Subscription
    {
        $active = Subscription::where('store_id', $store->id)->where('status', 'active')->first();
        if ($active) {
            return $active;
        }

        $plan = $this->plan();

        $store->forceFill(['plan_id' => $plan->id])->save();

        return Subscription::create([
            'store_id' => $store->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'started_at' => now(),
        ]);
    }
}
