<?php

namespace App\Modules\Orders\Models;

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
        'quantity' => 'integer',
        'raw_payload' => 'array',
    ];

    public function orderReturn()
    {
        return $this->belongsTo(OrderReturn::class, 'order_return_id');
    }

    public function orderItem()
    {
        return $this->belongsTo(OrderItem::class, 'order_item_id');
    }
}
