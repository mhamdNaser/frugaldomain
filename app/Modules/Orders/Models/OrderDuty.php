<?php

namespace App\Modules\Orders\Models;

use Illuminate\Database\Eloquent\Model;

class OrderDuty extends Model
{
    protected $fillable = [
        'store_id',
        'order_id',
        'shopify_order_id',
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

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function itemDuties()
    {
        return $this->hasMany(OrderItemDuty::class, 'order_duty_id');
    }
}
