<?php

namespace App\Modules\Billing\Models;

use App\Modules\Orders\Models\Order;
use Illuminate\Database\Eloquent\Model;

class Refund extends Model
{
    protected $fillable = [
        'store_id',
        'order_id',
        'shopify_refund_id',
        'note',
        'total',
        'currency',
        'raw_payload',
        'processed_at',
        'shopify_created_at',
        'shopify_updated_at',
    ];

    protected $casts = [
        'total' => 'decimal:2',
        'raw_payload' => 'array',
        'processed_at' => 'datetime',
        'shopify_created_at' => 'datetime',
        'shopify_updated_at' => 'datetime',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function items()
    {
        return $this->hasMany(RefundItem::class);
    }

    public function transactions()
    {
        return $this->hasMany(PaymentTransaction::class);
    }
}
