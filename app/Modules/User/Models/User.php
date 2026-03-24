<?php

namespace App\Modules\User\Models;

// use Filament\Panel; if you are using Filament and want to implement access control for the admin panel, you can import the Panel class and implement the canAccessPanel method in your User model.

use App\Modules\Locale\Models\City;
use App\Modules\Locale\Models\Country;
use App\Modules\Locale\Models\State;
use App\Modules\Stores\Models\Store;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;


class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens, HasRoles;

    protected $keyType = 'string';

    // If you are using UUIDs, you should set the $incrementing property to false and the $keyType property to 'string':
    // public $incrementing = false;
    // protected $guard_name = 'web';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'first_name',
        'medium_name',
        'last_name',
        'password',
        'phone',
        'address_1',
        'address_2',
        'address_3',
        'country_id',
        'state_id',
        'city_id',
        'status',
        'email',
        'image',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function country()
    {
        return $this->belongsTo(Country::class);
    }

    public function state()
    {
        return $this->belongsTo(State::class);
    }

    public function city()
    {
        return $this->belongsTo(City::class);
    }

    public function store()
    {
        return $this->hasOne(Store::class, 'owner_id');
    }

    public function hasStore(): bool
    {
        return $this->store()->exists();
    }

    // public function canAccessPanel(Panel $panel): bool
    // {
    //     // بما أن عندك Panel واحد اسمه admin:
    //     if ($panel->getId() === 'admin') {
    //         return $this->hasAnyRole(['admin', 'tenant']);
    //     }

    //     return false;
    // }

    // public function getFilamentName(): string
    // {
    //     $fullName = trim(($this->first_name ?? '') . ' ' . ($this->medium_name ?? '') . ' ' . ($this->last_name ?? ''));
    //     return $fullName !== '' ? $fullName : ($this->username ?? $this->email ?? 'User');
    // }
}
