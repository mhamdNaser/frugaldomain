<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Point extends Model
{
    use HasFactory;

    protected $fillable = [
        'frame_id',
        'point_id',
        'x',
        'y',
        'dx',
        'dy',
        'vx',
        'vy',
        'angle',
        'state',
        'pressure',
    ];

    public function frame()
    {
        return $this->belongsTo(Frame::class);
    }
}
