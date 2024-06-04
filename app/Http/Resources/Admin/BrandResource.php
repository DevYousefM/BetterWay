<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Log;

class BrandResource extends JsonResource
{

    public function toArray($request){
        $User = auth('user')->user();
        if($User){
            $UserLanguage = AdminLanguage($User->UserLanguage);
            $BrandName = "BrandName".$UserLanguage;
            $BrandDesc = "BrandDesc".$UserLanguage;
            $BrandPolicy = "BrandPolicy".$UserLanguage;
        }else{
            $BrandName = "BrandNameEn";
            $BrandDesc = "BrandDescEn";
            $BrandPolicy = "BrandPolicyEn";
        }

        return [
            'IDBrand'             => $this->IDBrand,
            'IDUser'              => $this->IDUser,
            'BrandName'           => $this->$BrandName,
            'BrandDesc'           => ($this->$BrandDesc) ? $this->$BrandDesc : '',
            'BrandPolicy'         => ($this->$BrandPolicy) ? $this->$BrandPolicy : '',
            'BrandLogo'           => ($this->BrandLogo) ? asset($this->BrandLogo) : '',
            'SalesName'           => ($this->UserName) ? $this->UserName : '',
            'SalesPhone'          => ($this->UserPhone) ? $this->UserPhone : '',
            'BrandNumber'         => $this->BrandNumber,
            'BrandEmail'          => $this->BrandEmail,
            'BrandRating'         => $this->BrandRating,
            'BrandStatus'         => $this->BrandStatus,
            'CreateDate'          => $this->created_at,
        ];
    }
}
