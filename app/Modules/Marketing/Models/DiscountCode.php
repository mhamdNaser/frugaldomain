<?php

namespace App\Modules\Marketing\Models;

use Illuminate\Database\Eloquent\Model;

class DiscountCode extends Model
{
    protected $fillable = [
        'store_id',
        'discount_id',
        'shopify_discount_code_id',
        'code',
        'usage_count',
        'raw_payload',
    ];

    protected $casts = [
        'usage_count' => 'integer',
        'raw_payload' => 'array',
    ];

    public function discount()
    {
        return $this->belongsTo(Discount::class);
    }
}
