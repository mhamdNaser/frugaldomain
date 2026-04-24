<?php

namespace App\Modules\Stores\Models;

use App\Modules\Catalog\Models\Option;
use App\Modules\CMS\Models\File;
use App\Modules\User\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Store extends Model
{
    use HasUuids, SoftDeletes;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'owner_id',
        'shopify_store_id',
        'shopify_domain',
        'shopify_access_token',
        'shopify_webhook_secret',
        'name',
        'email',
        'currency',
        'timezone',
        'plan',
        'status',
        'installed_at',
        'uninstalled_at',
        'last_synced_at',
    ];

    protected $casts = [
        'installed_at' => 'datetime',
        'uninstalled_at' => 'datetime',
        'last_synced_at' => 'datetime',
    ];

    // Owner
    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    // Settings
    public function settings(): HasOne
    {
        return $this->hasOne(StoreSetting::class, 'store_id');
    }

    // Branding
    public function branding(): HasOne
    {
        return $this->hasOne(StoreBranding::class, 'store_id');
    }

    // Devices
    public function devices(): HasMany
    {
        return $this->hasMany(Device::class, 'store_id');
    }

    public function options()
    {
        return $this->hasMany(Option::class, 'store_id');
    }

    // App Sessions
    public function appSessions(): HasMany
    {
        return $this->hasMany(AppSession::class, 'store_id');
    }

    // Media
    public function files(): MorphMany
    {
        return $this->morphMany(File::class, 'fileable');
    }
}
