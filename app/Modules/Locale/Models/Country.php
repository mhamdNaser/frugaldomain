<?php

namespace App\Modules\Locale\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Country extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'iso_code',
        'phone_code',
    ];

    /**
     * Get all states for this country
     */
    public function states()
    {
        return $this->hasMany(State::class);
    }
}
