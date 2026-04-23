<?php

namespace App\Modules\Fulfillment\Models;

use App\Modules\Orders\Models\OrderReturn;
use Illuminate\Database\Eloquent\Model;

class ReverseFulfillment extends Model
{
    protected $table = 'reverse_fulfillments';

    protected $fillable = [
        'store_id',
        'order_return_id',
        'shopify_reverse_fulfillment_order_id',
        'status',
        'raw_payload',
        'shopify_created_at',
        'shopify_updated_at',
    ];

    protected $casts = [
        'raw_payload' => 'array',
        'shopify_created_at' => 'datetime',
        'shopify_updated_at' => 'datetime',
    ];

    public function orderReturn()
    {
        return $this->belongsTo(OrderReturn::class, 'order_return_id');
    }
}
