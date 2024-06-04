<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Log;

class BranchResource extends JsonResource
{

    public function toArray($request){
        $User = auth('user')->user();
        if($User){
            $UserLanguage = AdminLanguage($User->UserLanguage);
            $AreaName = "AreaName".$UserLanguage;
            $CityName = "CityName".$UserLanguage;
            $BrandName = "BrandName".$UserLanguage;
            $BranchAddress = "BranchAddress".$UserLanguage;
        }else{
            $CityName = "CityNameEn";
            $AreaName = "AreaNameEn";
            $BrandName = "BrandNameEn";
            $BranchAddress = "BranchAddressEn";
        }


        return [
            'IDBranch'               => $this->IDBranch,
            'IDBrand'                => $this->IDBrand,
            'BrandName'              => $this->$BrandName,
            'BranchAddress'          => $this->$BranchAddress,
            'BranchLatitude'         => $this->BranchLatitude,
            'BranchLongitude'        => $this->BranchLongitude,
            'BranchPhone'            => $this->BranchPhone,
            'BranchStatus'           => $this->BranchStatus,
            'IDCity'                 => $this->IDCity,
            'CityName'               => $this->$CityName,
            'IDArea'                 => $this->IDArea,
            'AreaName'               => $this->$AreaName,
            'CreateDate'             => $this->created_at,
        ];
    }
}
