<?php

namespace App\Modules\Fulfillment\Models;

use Illuminate\Database\Eloquent\Model;

class OrderReturn extends Model
{
    protected $table = 'order_returns';

    protected $fillable = [
        'store_id',
        'order_id',
        'shopify_return_id',
        'status',
        'name',
        'requested_at',
        'opened_at',
        'closed_at',
        'raw_payload',
    ];

    protected $casts = [
        'requested_at' => 'datetime',
        'opened_at' => 'datetime',
        'closed_at' => 'datetime',
        'raw_payload' => 'array',
    ];
}

