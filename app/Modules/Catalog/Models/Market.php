<?php

namespace App\Modules\Catalog\Models;

use Illuminate\Database\Eloquent\Model;

class Market extends Model
{
    protected $fillable = [
        'store_id',
        'shopify_market_id',
        'name',
        'handle',
        'currency',
        'enabled',
        'is_primary',
        'raw_payload',
    ];

    protected $casts = [
        'enabled' => 'boolean',
        'is_primary' => 'boolean',
        'raw_payload' => 'array',
    ];
}

