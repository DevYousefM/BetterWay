<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Log;

class EventResource extends JsonResource
{

    public function toArray($request){
        $User = auth('user')->user();
        if($User){
            $UserLanguage = AdminLanguage($User->UserLanguage);
            $AreaName = "AreaName".$UserLanguage;
            $CityName = "CityName".$UserLanguage;
            $EventTitle = "EventTitle".$UserLanguage;
            $EventDesc = "EventDesc".$UserLanguage;
            $EventPolicy = "EventPolicy".$UserLanguage;
        }else{
            $CityName = "CityNameEn";
            $AreaName = "AreaNameEn";
            $EventTitle = "EventTitleEn";
            $EventDesc = "EventDescEn";
            $EventPolicy = "EventPolicyEn";
        }

        return [
            'IDEvent'              => $this->IDEvent,
            'EventTitle'           => $this->$EventTitle,
            'EventDesc'            => $this->$EventDesc,
            'EventPolicy'          => $this->$EventPolicy,
            'CityName'             => $this->$CityName,
            'AreaName'             => $this->$AreaName,
            'EventStatus'          => $this->EventStatus,
            'EventStartTime'       => $this->EventStartTime,
            'EventEndTime'         => $this->EventEndTime,
            'InstallmentEndDate'   => $this->EventInstallmentEndDate,
            'EventLatitude'        => $this->EventLatitude,
            'EventLongitude'       => $this->EventLongitude,
            'EventAddress'         => $this->EventAddress,
            'EventPrice'           => $this->EventPrice,
            'EventPoints'          => $this->EventPoints,
            'EventMaxNumber'       => $this->EventMaxNumber,
            'EventClientNumber'    => $this->EventClientNumber,
        ];
    }
}