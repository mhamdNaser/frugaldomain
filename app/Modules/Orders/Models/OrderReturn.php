<?php

namespace App\Modules\Orders\Models;

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

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function items()
    {
        return $this->hasMany(OrderReturnItem::class, 'order_return_id');
    }
}
