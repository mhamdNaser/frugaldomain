<?php

namespace App\Modules\Fulfillment\Models;

use Illuminate\Database\Eloquent\Model;

class OrderReturnItem extends Model
{
    protected $table = 'order_return_items';

    protected $fillable = [
        'store_id',
        'order_return_id',
        'order_item_id',
        'shopify_return_line_item_id',
        'shopify_line_item_id',
        'quantity',
        'reason',
        'note',
        'raw_payload',
    ];

    protected $casts = [
        'raw_payload' => 'array',
    ];
}

