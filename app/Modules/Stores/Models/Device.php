<?php

namespace App\Modules\Stores\Models;

use App\Modules\User\Models\Customer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Device extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'store_id',
        'customer_id',
        'device_token',
        'platform',
        'app_version',
        'is_active',
        'last_active_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'last_active_at' => 'datetime',
    ];

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class, 'store_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function appSessions()
    {
        return $this->hasMany(AppSession::class, 'device_id');
    }
}
