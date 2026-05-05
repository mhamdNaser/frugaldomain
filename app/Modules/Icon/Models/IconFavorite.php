<?php

namespace App\Modules\Icon\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class IconFavorite extends Model
{
    use SoftDeletes;

    protected $table = 'icon_favorites';

    protected $fillable = [
        'user_id',
        'icon_id',
    ];

    public function user()
    {
        return $this->belongsTo(\App\Modules\User\Models\User::class);
    }

    public function icon()
    {
        return $this->belongsTo(Icon::class);
    }
}
