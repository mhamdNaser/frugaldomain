<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Gesture extends Model
{
    use HasFactory;

    protected $fillable = [
        'character', 'user_id', 'device_id',
        'start_time', 'end_time', 'duration_ms',
        'frame_count', 'notes'
    ];

    public function frames()
    {
        return $this->hasMany(Frame::class);
    }
}
