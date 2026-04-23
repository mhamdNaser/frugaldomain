<?php

namespace App\Modules\Catalog\Models;

use Illuminate\Database\Eloquent\Model;

class SellingPlanGroup extends Model
{
    protected $fillable = [
        'store_id',
        'shopify_selling_plan_group_id',
        'name',
        'app_id',
        'options',
        'summary',
        'raw_payload',
    ];

    protected $casts = [
        'options' => 'array',
        'raw_payload' => 'array',
    ];
}

