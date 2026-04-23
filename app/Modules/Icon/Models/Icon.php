<?php

namespace App\Modules\Icon\Models;

use App\Modules\User\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Icon extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'title',
        'description',
        'category_id',
        'user_id',
        'is_premium',
        'download_count',
        'tags',
        'is_active',
        'file_svg',
        'file_png'
    ];

    protected $casts = [
        'tags' => 'array',
        'is_active' => 'boolean',
        'is_premium' => 'boolean',
    ];

    public function category()
    {
        return $this->belongsTo(IconCategories::class, 'category_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function files()
    {
        return $this->hasMany(IconFiles::class, 'icon_id');
    }

    public function downloads()
    {
        return $this->hasMany(IconDownloads::class);
    }
}
