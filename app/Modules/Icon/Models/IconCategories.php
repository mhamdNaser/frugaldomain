<?php

namespace App\Modules\Icon\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IconCategories extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'parent_id',
        'icon_count',
        'is_active',
    ];

    public function parent()
    {
        return $this->belongsTo(IconCategories::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(IconCategories::class, 'parent_id');
    }

    public function icons()
    {
        return $this->hasMany(Icon::class, 'category_id');
    }
}
