<?php

namespace App\Http\Resources\App;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Log;

class BranchResource extends JsonResource
{

    public function toArray($request)
    {
        $Client = auth('client')->user();
        if ($Client) {
            $ClientLanguage = LocalAppLanguage($Client->ClientAppLanguage);
            $BranchAddress = "BranchAddress" . $ClientLanguage;
            $AreaName = "AreaName" . $ClientLanguage;
            $CityName = "CityName" . $ClientLanguage;
        } else {
            $BranchAddress = "BranchAddressEn";
            $AreaName = "AreaNameEn";
            $CityName = "CityNameEn";
        }

        return [
            'IDCity'              => $this->IDCity,
            'CityName'            => $this->$CityName,
            'AreaName'            => $this->$AreaName,
            'BranchAddress'       => $this->$BranchAddress,
            'BranchPhone'         => $this->BranchPhone,
            'BranchLatitude'      => $this->BranchLatitude,
            'BranchLongitude'     => $this->BranchLongitude,
        ];
    }
}
