<?php

namespace App\Modules\Stores\Models;

use App\Modules\User\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Store extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'owner_id',
        'shopify_domain',
        'email',
        'currency',
        'plan',
        'status',
        'installed_at',
    ];


    public function Owner()
    {
        return $this->belongsTo(User::class);
    }
}
