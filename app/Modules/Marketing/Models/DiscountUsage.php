<?php

namespace App\Modules\Marketing\Models;

use App\Modules\Orders\Models\Order;
use Illuminate\Database\Eloquent\Model;

class DiscountUsage extends Model
{
    protected $fillable = [
        'store_id',
        'discount_id',
        'order_id',
        'shopify_order_id',
        'code',
        'usage_count',
        'total_sales',
        'currency',
        'raw_payload',
    ];

    protected $casts = [
        'usage_count' => 'integer',
        'total_sales' => 'decimal:2',
        'raw_payload' => 'array',
    ];

    public function discount()
    {
        return $this->belongsTo(Discount::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
