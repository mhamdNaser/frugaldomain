<?php

namespace App\Modules\Orders\Models;

use Illuminate\Database\Eloquent\Model;

class OrderChannel extends Model
{
    protected $fillable = [
        'store_id',
        'order_id',
        'shopify_order_id',
        'source_name',
        'source_identifier',
        'channel_id',
        'channel_name',
        'app_id',
        'app_title',
        'raw_payload',
    ];

    protected $casts = [
        'raw_payload' => 'array',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
