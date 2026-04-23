<?php

namespace App\Modules\Marketing\Models;

use Illuminate\Database\Eloquent\Model;

class Discount extends Model
{
    protected $fillable = [
        'store_id',
        'shopify_discount_id',
        'discount_type',
        'method',
        'title',
        'status',
        'summary',
        'short_summary',
        'usage_limit',
        'usage_count',
        'total_sales',
        'currency',
        'starts_at',
        'ends_at',
        'raw_payload',
        'shopify_updated_at',
    ];

    protected $casts = [
        'usage_limit' => 'integer',
        'usage_count' => 'integer',
        'total_sales' => 'decimal:2',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'raw_payload' => 'array',
        'shopify_updated_at' => 'datetime',
    ];

    public function codes()
    {
        return $this->hasMany(DiscountCode::class);
    }

    public function usages()
    {
        return $this->hasMany(DiscountUsage::class);
    }
}
