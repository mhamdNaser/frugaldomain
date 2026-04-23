<?php

namespace App\Modules\Icon\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class IconDownloads extends Model
{
    use SoftDeletes;

    protected $table = 'icon_downloads';

    protected $fillable = [
        'user_id',
        'icon_id',
        'icon_file_id',
        'download_type',
        'ip_address',
        'downloaded_at',
    ];

    protected $casts = [
        'downloaded_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(\App\Modules\User\Models\User::class);
    }

    public function icon()
    {
        return $this->belongsTo(Icon::class);
    }

    public function iconFile()
    {
        return $this->belongsTo(IconFiles::class, 'icon_file_id');
    }
}
