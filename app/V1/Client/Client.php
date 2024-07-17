<?php

namespace App\V1\Client;

use App\V1\General\Nationality;
use App\V1\Plan\PlanNetwork;
use App\V1\Plan\PlanProduct;
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
    protected $guarded = [];

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

    public function getNationality()
    {
        return $nationality = Nationality::where('IDNationality', $this->IDNationality)->value('NationalityNameAr');
    }
    public function planNetworks()
    {
        return $this->hasMany(PlanNetwork::class, 'IDClient', 'IDClient');
    }
    public function getPlanProductPrice()
    {
        // Assuming the client has only one plan network
        $planNetwork = $this->planNetworks()->first();

        if ($planNetwork) {
            $planProduct = $planNetwork->planProduct;

            if ($planProduct) {
                return $planProduct->PlanProductPrice;
            }
        }

        return null; // Or some default value if no product or price is found
    }
    public function clientdocuments()
    {
        return $this->hasMany(ClientDocument::class, "IDClient");
    }
}
