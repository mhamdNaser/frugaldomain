<?php

namespace App\Modules\Stores\Models;

use App\Modules\User\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Store extends Model
{
    use HasUuids;
    use SoftDeletes;


    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'owner_id',
        'shopify_store_id',
        'shopify_domain',
        'shopify_access_token',
        'name',
        'email',
        'currency',
        'timezone',
        'plan',
        'status',
        'installed_at',
    ];


    public function Owner()
    {
        return $this->belongsTo(User::class);
    }
}
