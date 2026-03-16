<?php

namespace App\Modules\Locale\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class City extends Model
{
    use HasFactory;

    protected $fillable = [
        'state_id',
        'name',
        'postal_code',
    ];

    /**
     * Get the state this city belongs to
     */
    public function state()
    {
        return $this->belongsTo(State::class);
    }
}
