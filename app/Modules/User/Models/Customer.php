<?php

namespace App\Modules\User\Models;

use App\Modules\Catalog\Models\SellingPlanSubscription;
use App\Modules\Orders\Models\Cart;
use App\Modules\Orders\Models\DraftOrder;
use App\Modules\Orders\Models\Order;
use App\Modules\Stores\Models\AppSession;
use App\Modules\Stores\Models\Device;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'store_id',
        'shopify_customer_id',
        'first_name',
        'last_name',
        'display_name',
        'email',
        'phone',
        'password',
        'email_verified_at',
        'status',
        'state',
        'tags',
        'note',
        'verified_email',
        'tax_exempt',
        'orders_count',
        'total_spent',
        'currency',
        'default_address_id',
        'raw_payload',
        'shopify_created_at',
        'shopify_updated_at',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'tags' => 'array',
        'verified_email' => 'boolean',
        'tax_exempt' => 'boolean',
        'orders_count' => 'integer',
        'total_spent' => 'decimal:2',
        'raw_payload' => 'array',
        'shopify_created_at' => 'datetime',
        'shopify_updated_at' => 'datetime',
    ];

    public function addresses()
    {
        return $this->hasMany(CustomerAddress::class);
    }

    public function defaultAddress()
    {
        return $this->belongsTo(CustomerAddress::class, 'default_address_id');
    }

    public function marketingConsent()
    {
        return $this->hasOne(CustomerMarketingConsent::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function draftOrders()
    {
        return $this->hasMany(DraftOrder::class);
    }

    public function carts()
    {
        return $this->hasMany(Cart::class);
    }

    public function devices()
    {
        return $this->hasMany(Device::class);
    }

    public function appSessions()
    {
        return $this->hasMany(AppSession::class);
    }

    public function sellingPlanSubscriptions()
    {
        return $this->hasMany(SellingPlanSubscription::class);
    }
}
