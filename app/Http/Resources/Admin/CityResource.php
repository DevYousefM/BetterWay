<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Log;

class CityResource extends JsonResource
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
        }

        return [
            'IDCountry'              => $this->IDCountry,
            'CountryName'            => $this->$CountryName,
            'IDCity'                 => $this->IDCity,
            'CityName'               => $this->$CityName,
            'CityActive'             => $this->CityActive,
        ];
    }
}
