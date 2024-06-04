<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Log;

class PlanProductResource extends JsonResource
{

    public function toArray($request){
        $User = auth('user')->user();
        if($User){
            $UserLanguage = AdminLanguage($User->UserLanguage);
            $PlanName = "PlanName".$UserLanguage;
            $PlanProductName = "PlanProductName".$UserLanguage;
            $PlanProductDesc = "PlanProductDesc".$UserLanguage;
            $PlanProductAddress = "PlanProductAddress".$UserLanguage;
        }else{
            $PlanName = "PlanNameEn";
            $PlanProductName = "PlanProductNameEn";
            $PlanProductDesc = "PlanProductDescEn";
            $PlanProductAddress = "PlanProductAddressEn";
        }

        return [
            'IDPlanProduct'             => $this->IDPlanProduct ,
            'PlanProductName'           => $this->$PlanProductName,
            'PlanProductDesc'           => $this->$PlanProductDesc,
            'PlanProductAddress'        => $this->$PlanProductAddress ? $this->$PlanProductAddress : "",
            'PlanProductPhone'          => $this->PlanProductPhone ? $this->PlanProductPhone : "",
            'PlanProductStatus'         => $this->PlanProductStatus,
            'PlanProductPrice'          => $this->PlanProductPrice,
            'PlanProductPoints'         => $this->PlanProductPoints,
            'PlanProductRewardPoints'   => $this->PlanProductRewardPoints,
            'PlanProductReferralPoints' => $this->PlanProductReferralPoints,
            'PlanProductUplinePoints'   => $this->PlanProductUplinePoints,
            'AgencyNumber'              => $this->AgencyNumber,
            'CardNumber'                => $this->CardNumber,
            'PlanProductLatitude'       => $this->PlanProductLatitude,
            'PlanProductLongitude'      => $this->PlanProductLongitude,
            'PlanName'                  => $this->$PlanName,
            'PlanProductGallery'        => $this->PlanProductGallery ? $this->PlanProductGallery : [],
            'CreateDate'                => $this->created_at,
        ];
    }
}