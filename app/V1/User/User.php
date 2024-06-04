<?php

namespace App\V1\User;

use Illuminate\Database\Eloquent\Model;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements JWTSubject
{
    use Notifiable;
    
    protected $table = 'users';
    protected $primaryKey = 'IDUser';
    protected $guard = 'user';

    protected $hidden = [
        'password',
    ];

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }
    public function getJWTCustomClaims()
    {
        return [];
    }
    public function getAuthIdentifier()
    {
        return $this->IDUser;
    }
    public function getAuthPassword()
    {
        return $this->UserPassword;
    }
    public function getRememberToken()
    {
    }
    public function setRememberToken($value)
    {
    }
    public function getRememberTokenName()
    {
    }
}
