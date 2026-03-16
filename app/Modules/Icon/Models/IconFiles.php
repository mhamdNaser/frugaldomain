<?php

namespace App\Modules\Icon\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IconFiles extends Model
{
    use HasFactory;

    protected $fillable = [
        'icon_id',
        'file_name',
        'file_path',
        'file_size',
        'file_type',
        'dimensions'
    ];

    protected $casts = [
        'file_size' => 'integer',
    ];

    public function icon()
    {
        return $this->belongsTo(Icon::class, 'icon_id');
    }
}
