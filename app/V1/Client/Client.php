<?php

namespace App\V1\Client;

use App\Http\Resources\App\PlanNetworkResource;
use App\V1\General\Nationality;
use App\V1\Plan\PlanNetwork;
use App\V1\Plan\PlanNetworkAgency;
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
    public function getRememberToken() {}
    public function setRememberToken($value) {}
    public function getRememberTokenName() {}

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
    public function referrals()
    {
        return $this->hasMany(Client::class, 'IDReferral');
    }
    public function visits()
    {
        return $this->hasMany(ClientBrandProduct::class, 'IDClient');
    }
    public function getPersonsAttribute()
    {
        $IDClient = $this->IDClient;
        $records = PlanNetwork::where('PlanNetworkPath', 'like', '%' . $IDClient . '%')->get();

        $extractedIDs = [];
        foreach ($records as $record) {
            $extractedIDs[] = $record->IDClient;
        }
        $extractedIDs = array_unique($extractedIDs);
        return Client::whereIn('IDClient', $extractedIDs)->get();
    }
    public function position()
    {
        if (!$this->IDPosition) return null;
        return $this->hasOne(Position::class, 'IDPosition');
    }

    public function getRightPersonsAttribute()
    {
        $IDClient = $this->IDClient;
        $GetRightClient = PlanNetwork::where("IDParentClient", $IDClient)->where("PlanNetworkPosition", "RIGHT")->first();

        $records = PlanNetwork::where('PlanNetworkPath', 'like', '%' . $GetRightClient->IDClient . '%')->get();

        $extractedIDs = [];
        foreach ($records as $record) {
            $extractedIDs[] = $record->IDClient;
        }
        $extractedIDs[] = $GetRightClient->IDClient;
        $extractedIDs = array_unique($extractedIDs);
        return Client::whereIn('IDClient', $extractedIDs)->get();
    }
    public function getLeftPersonsAttribute()
    {
        $IDClient = $this->IDClient;
        $GetLeftClient = PlanNetwork::where("IDParentClient", $IDClient)->where("PlanNetworkPosition", "LEFT")->first();

        $records = PlanNetwork::where('PlanNetworkPath', 'like', '%' . $GetLeftClient->IDClient . '%')->get();

        $extractedIDs = [];
        foreach ($records as $record) {
            $extractedIDs[] = $record->IDClient;
        }
        $extractedIDs[] = $GetLeftClient->IDClient;
        $extractedIDs = array_unique($extractedIDs);
        return Client::whereIn('IDClient', $extractedIDs)->get();
    }
    public function points_history()
    {
        return $this->hasMany(ClientPointsLedger::class, "IDClient");
    }
    public function getChequesHistoryAttribute()
    {
        return ClientLedger::where('IDClient', $this->IDClient)->where('ClientLedgerSource', 'CHEQUE')->get();
    }
}
