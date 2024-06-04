<?php

namespace App\Http\Resources\App;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Log;

class BrandResource extends JsonResource
{

    public function toArray($request){
        $Client = auth('client')->user();
        if($Client){
            $ClientLanguage = LocalAppLanguage($Client->ClientLanguage);
            $BrandName = "BrandName".$ClientLanguage;
            $BrandDesc = "BrandDesc".$ClientLanguage;
            $BrandPolicy = "BrandPolicy".$ClientLanguage;
        }else{
            $BrandName = "BrandNameEn";
            $BrandDesc = "BrandDescEn";
            $BrandPolicy = "BrandPolicyEn";
        }

        return [
            'IDBrand'             => $this->IDBrand,
            'BrandName'           => $this->$BrandName,
            'BrandDesc'           => ($this->BrandDesc) ? $this->BrandDesc : '',
            'BrandPolicy'         => ($this->BrandPolicy) ? $this->BrandPolicy : '',
            'BrandLogo'           => ($this->BrandLogo) ? asset($this->BrandLogo) : '',
            'BrandNumber'         => $this->BrandNumber,
            'BrandRating'         => $this->BrandRating,
        ];
    }
}
