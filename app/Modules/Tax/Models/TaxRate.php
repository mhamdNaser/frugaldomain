<?php

namespace App\Modules\Tax\Models;

use Illuminate\Database\Eloquent\Model;

class TaxRate extends Model
{
    protected $fillable = [
        'store_id',
        'title',
        'country_code',
        'province_code',
        'rate',
        'rate_percentage',
        'applies_to_shipping',
        'is_active',
        'source',
        'raw_payload',
    ];

    protected $casts = [
        'rate' => 'decimal:6',
        'rate_percentage' => 'decimal:6',
        'applies_to_shipping' => 'boolean',
        'is_active' => 'boolean',
        'raw_payload' => 'array',
    ];
}
