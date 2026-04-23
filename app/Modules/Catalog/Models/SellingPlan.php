<?php

namespace App\Modules\Catalog\Models;

use Illuminate\Database\Eloquent\Model;

class SellingPlan extends Model
{
    protected $fillable = [
        'store_id',
        'selling_plan_group_id',
        'shopify_selling_plan_id',
        'name',
        'category',
        'billing_policy',
        'delivery_policy',
        'pricing_policies',
        'raw_payload',
    ];

    protected $casts = [
        'billing_policy' => 'array',
        'delivery_policy' => 'array',
        'pricing_policies' => 'array',
        'raw_payload' => 'array',
    ];
}

