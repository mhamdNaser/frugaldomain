<?php

namespace App\Modules\Inventory\Models;

use Illuminate\Database\Eloquent\Model;

class Location extends Model
{
    protected $fillable = [
        'store_id',
        'shopify_location_id',
        'name',
        'address',
        'city',
        'country',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
