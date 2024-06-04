<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Log;

class CountryResource extends JsonResource
{

    public function toArray($request){
        $User = auth('user')->user();
        if($User){
            $UserLanguage = AdminLanguage($User->UserLanguage);
            $CountryName = "CountryName".$UserLanguage;
        }else{
            $CountryName = "CountryNameEn";
        }


        return [
            'IDCountry'            => $this->IDCountry,
            'CountryName'          => $this->$CountryName,
            'CountryActive'        => $this->CountryActive,
            'CountryTimeZone'      => $this->CountryTimeZone,
            'CountryCode'          => $this->CountryCode,
        ];
    }
}
