<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use App\Notifications\VerifyEmail;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject, MustVerifyEmail
{
    use HasApiTokens, HasFactory, Notifiable;


    const ROLE_SUPERADMIN = 'superadmin';
    const ROLE_USER = 'user';
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'status',
        'role',
        'photo'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [];
    }

    public function isSuperadmin()
    {
        // 是否為superadmin
        return $this->role === self::ROLE_SUPERADMIN;
    }

    // In User.php
    public function isHost(Pokemon $pokemon)
    {
        // 當前登入者的id是否為寶可夢主人的id
        //TODO 為何$this->id可以call到當前使用者的id
        return $this->id === $pokemon->user_id || $this->isSuperadmin();
    }

    public function pokemons()
    {
        return $this->hasMany(Pokemon::class);
    }

    public function cartItems()
    {
        return $this->hasOne(CartItem::class);
    }
    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function sendEmailVerificationNotification()
    {
        $this->notify(new VerifyEmail); // 使用新的 VerifyEmail 通知类
    }
}
