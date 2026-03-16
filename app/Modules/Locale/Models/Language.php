<?php

namespace App\Modules\Locale\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Language extends Model
{
    use HasFactory;

    // اسم الجدول
    protected $table = 'languages';

    // الأعمدة القابلة للتعديل
    protected $fillable = [
        'code',
        'name',
        'slug',
        'default',
        'status',
        'direction'
    ];

    // الأعمدة التي يجب تحويلها لأنواع معينة
    protected $casts = [
        'default' => 'boolean',
        'status' => 'boolean',
    ];
}
