<?php

namespace App\Modules\Fulfillment\Models;

use Illuminate\Database\Eloquent\Model;

class Exchange extends Model
{
    protected $fillable = [
        'store_id',
        'order_return_id',
        'shopify_exchange_line_item_id',
        'shopify_line_item_id',
        'title',
        'quantity',
        'status',
        'raw_payload',
    ];

    protected $casts = [
        'raw_payload' => 'array',
    ];
}

