<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Log;
use App\V1\Client\Client;

class ClientBonanzaResource extends JsonResource
{

    public function toArray($request){
        $User = auth('user')->user();
        if($User){
            $UserLanguage = AdminLanguage($User->UserLanguage);
            $BonanzaTitle = "BonanzaTitle".$UserLanguage;
        }else{
            $BonanzaTitle = "BonanzaTitleEn";
        }

        return [
            'IDClient'               => $this->IDClient,
            'ClientName'             => $this->ClientName,
            'ClientPhone'            => $this->ClientPhone,
            'BonanzaTitle'           => $this->$BonanzaTitle,
            'ClientLeftPoints'       => $this->ClientLeftPoints,
            'ClientRightPoints'      => $this->ClientRightPoints,
            'ClientTotalPoints'      => $this->ClientTotalPoints,
            'ClientProductValue'     => $this->ClientProductValue,
            'ClientVisitNumber'      => $this->ClientVisitNumber,
            'BrandVisit'             => $this->BrandVisit,
            'BonanzaReferralNumber'  => $this->BonanzaReferralNumber,
        ];
    }
}
