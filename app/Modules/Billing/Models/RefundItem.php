<?php

namespace App\Modules\Billing\Models;

use App\Modules\Orders\Models\OrderItem;
use Illuminate\Database\Eloquent\Model;

class RefundItem extends Model
{
    protected $fillable = [
        'store_id',
        'refund_id',
        'order_item_id',
        'shopify_refund_line_item_id',
        'shopify_line_item_id',
        'quantity',
        'restock_type',
        'restocked',
        'subtotal',
        'tax',
        'total',
        'currency',
        'raw_payload',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'restocked' => 'boolean',
        'subtotal' => 'decimal:2',
        'tax' => 'decimal:2',
        'total' => 'decimal:2',
        'raw_payload' => 'array',
    ];

    public function refund()
    {
        return $this->belongsTo(Refund::class);
    }

    public function orderItem()
    {
        return $this->belongsTo(OrderItem::class);
    }
}
