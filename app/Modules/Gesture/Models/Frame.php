<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Frame extends Model
{
    use HasFactory;

    protected $fillable = [
        'gesture_id', 'frame_id', 'timestamp',
        'points_count', 'delta_ms',
    ];

    public function gesture()
    {
        return $this->belongsTo(Gesture::class);
    }

    public function points()
    {
        return $this->hasMany(Point::class);
    }
}
