<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Log;
use App\V1\Location\Country;
use App\V1\Location\City;

class UserResource extends JsonResource
{

    public function toArray($request){
        $User = auth('user')->user();
        if($User){
            $UserLanguage = AdminLanguage($User->UserLanguage);
            $BrandName = "BrandName".$UserLanguage;
            $BranchAddress = "BranchAddress".$UserLanguage;
        }else{
            $BrandName = "BrandNameEn";
            $BranchAddress = "BranchAddressEn";
        }

        if($this->IDBranch){
            $this->RoleName = "Branch";
        }

        return [
            'IDUser'                 => $this->IDUser,
            'UserName'               => $this->UserName,
            'UserEmail'              => $this->UserEmail,
            'UserPhone'              => $this->UserPhone,
            'UserPhoneFlag'          => $this->UserPhoneFlag,
            'UserStatus'             => $this->UserStatus,
            'RoleName'               => $this->RoleName,
            'UserRank'               => $this->UserRank,
            'BrandName'              => $this->$BrandName ? $this->$BrandName : '',
            'BranchAddress'          => $this->$BranchAddress ? $this->$BranchAddress : '',
            'CreateDate'             => $this->created_at,
        ];
    }
}
