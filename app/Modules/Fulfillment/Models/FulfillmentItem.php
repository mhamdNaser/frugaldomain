<?php

namespace App\Modules\Fulfillment\Models;

use App\Modules\Orders\Models\OrderItem;
use Illuminate\Database\Eloquent\Model;

class FulfillmentItem extends Model
{
    protected $table = 'fulfillment_items';

    protected $fillable = [
        'store_id',
        'fulfillment_id',
        'order_item_id',
        'shopify_line_item_id',
        'quantity',
        'raw_payload',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'raw_payload' => 'array',
    ];

    public function fulfillment()
    {
        return $this->belongsTo(Fulfillment::class);
    }

    public function orderItem()
    {
        return $this->belongsTo(OrderItem::class);
    }
}
