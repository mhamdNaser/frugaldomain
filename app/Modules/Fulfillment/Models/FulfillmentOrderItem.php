<?php

namespace App\Modules\Fulfillment\Models;

use App\Modules\Orders\Models\OrderItem;
use Illuminate\Database\Eloquent\Model;

class FulfillmentOrderItem extends Model
{
    protected $table = 'fulfillment_order_items';

    protected $fillable = [
        'store_id',
        'fulfillment_order_id',
        'order_item_id',
        'shopify_fulfillment_order_line_item_id',
        'shopify_line_item_id',
        'total_quantity',
        'remaining_quantity',
        'raw_payload',
    ];

    protected $casts = [
        'total_quantity' => 'integer',
        'remaining_quantity' => 'integer',
        'raw_payload' => 'array',
    ];

    public function fulfillmentOrder()
    {
        return $this->belongsTo(FulfillmentOrder::class);
    }

    public function orderItem()
    {
        return $this->belongsTo(OrderItem::class);
    }
}
