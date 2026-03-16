<?php

namespace App\Modules\Locale\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class State extends Model
{
    use HasFactory;

    protected $fillable = [
        'country_id',
        'name',
        'code',
    ];

    /**
     * Get the country this state belongs to
     */
    public function country()
    {
        return $this->belongsTo(Country::class);
    }

    /**
     * Get all cities for this state
     */
    public function cities()
    {
        return $this->hasMany(City::class);
    }
}
