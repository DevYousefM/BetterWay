<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Log;

class AreaResource extends JsonResource
{

    public function toArray($request){
        $User = auth('user')->user();
        if($User){
            $UserLanguage = AdminLanguage($User->UserLanguage);
            $AreaName = "AreaName".$UserLanguage;
            $CountryName = "CountryName".$UserLanguage;
            $CityName = "CityName".$UserLanguage;
        }else{
            $CountryName = "CountryNameEn";
            $CityName = "CityNameEn";
            $AreaName = "AreaNameEn";
        }


        return [
            'IDCity'                 => $this->IDCity,
            'CountryName'            => $this->$CountryName,
            'CityName'               => $this->$CityName,
            'IDArea'                 => $this->IDArea,
            'AreaName'               => $this->$AreaName,
            'AreaActive'             => $this->AreaActive,
        ];
    }
}
