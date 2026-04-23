<?php

namespace App\Modules\Orders\Models;

use Illuminate\Database\Eloquent\Model;

class OrderItemDuty extends Model
{
    protected $fillable = [
        'store_id',
        'order_item_id',
        'order_duty_id',
        'shopify_line_item_id',
        'shopify_duty_id',
        'harmonized_system_code',
        'amount',
        'currency',
        'raw_payload',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'raw_payload' => 'array',
    ];

    public function orderDuty()
    {
        return $this->belongsTo(OrderDuty::class, 'order_duty_id');
    }

    public function orderItem()
    {
        return $this->belongsTo(OrderItem::class, 'order_item_id');
    }
}
