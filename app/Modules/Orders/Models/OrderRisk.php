<?php

namespace App\Modules\Orders\Models;

use Illuminate\Database\Eloquent\Model;

class OrderRisk extends Model
{
    protected $fillable = [
        'store_id',
        'order_id',
        'shopify_order_id',
        'assessment_id',
        'recommendation',
        'risk_level',
        'provider',
        'assessed_at',
        'facts',
        'raw_payload',
    ];

    protected $casts = [
        'assessed_at' => 'datetime',
        'facts' => 'array',
        'raw_payload' => 'array',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
