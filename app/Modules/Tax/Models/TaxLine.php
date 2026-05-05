<?php

namespace App\Modules\Tax\Models;

use App\Modules\Orders\Models\Order;
use App\Modules\Orders\Models\OrderItem;
use Illuminate\Database\Eloquent\Model;

class TaxLine extends Model
{
    protected $fillable = [
        'store_id',
        'order_id',
        'order_item_id',
        'shopify_tax_line_id',
        'source_key',
        'title',
        'rate',
        'rate_percentage',
        'price',
        'currency',
        'channel_liable',
        'source',
        'is_shipping',
        'raw_payload',
    ];

    protected $casts = [
        'rate' => 'decimal:6',
        'rate_percentage' => 'decimal:6',
        'price' => 'decimal:2',
        'channel_liable' => 'boolean',
        'is_shipping' => 'boolean',
        'raw_payload' => 'array',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function orderItem()
    {
        return $this->belongsTo(OrderItem::class);
    }
}
