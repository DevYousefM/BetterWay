<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Log;

class BonanzaResource extends JsonResource
{

    public function toArray($request){
        $User = auth('user')->user();
        if($User){
            $UserLanguage = AdminLanguage($User->UserLanguage);
            $BonanzaTitle = "BonanzaTitle".$UserLanguage;
        }else{
            $BonanzaTitle = "BonanzaTitleEn";
        }

        return [
            'IDBonanza'               => $this->IDBonanza,
            'BonanzaTitle'            => $this->$BonanzaTitle,
            'BonanzaLeftPoints'       => $this->BonanzaLeftPoints,
            'BonanzaRightPoints'      => $this->BonanzaRightPoints,
            'BonanzaTotalPoints'      => $this->BonanzaTotalPoints,
            'BonanzaVisitNumber'      => $this->BonanzaVisitNumber,
            'BonanzaProductValue'     => $this->BonanzaProductValue,
            'BonanzaReferralNumber'   => $this->BonanzaReferralNumber,
            'BonanzaStartTime'        => $this->BonanzaStartTime,
            'BonanzaEndTime'          => $this->BonanzaEndTime,
            'BonanzaRewardPoints'     => $this->BonanzaRewardPoints,
            'BonanzaChequeValue'      => $this->BonanzaChequeValue,
            'BonanzaStatus'           => $this->BonanzaStatus,
            'CreateDate'              => $this->created_at,
        ];
    }
}
