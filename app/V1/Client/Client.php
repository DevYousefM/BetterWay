<?php

namespace App\V1\Client;

use Illuminate\Database\Eloquent\Model;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Client extends Authenticatable implements JWTSubject
{
    use Notifiable;
    
    protected $table = 'clients';
    protected $primaryKey = 'IDClient';
    protected $guard = 'client';

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
        return $this->IDClient;
    }
    public function getAuthPassword()
    {
        return $this->ClientPassword;
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
