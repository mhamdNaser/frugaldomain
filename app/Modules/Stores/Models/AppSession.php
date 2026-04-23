<?php

namespace App\Modules\Stores\Models;

use App\Modules\User\Models\Customer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AppSession extends Model
{
    protected $fillable = [
        'store_id',
        'customer_id',
        'device_id',
        'access_token',
        'refresh_token',
        'expires_at',
        'is_revoked',
        'ip_address',
        'user_agent',
        'last_used_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'last_used_at' => 'datetime',
        'is_revoked' => 'boolean',
    ];

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class, 'store_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class, 'device_id');
    }
}
