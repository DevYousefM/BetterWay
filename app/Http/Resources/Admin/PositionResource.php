<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Log;

class PositionResource extends JsonResource
{

    public function toArray($request){
        $User = auth('user')->user();
        if($User){
            $UserLanguage = AdminLanguage($User->UserLanguage);
            $PositionTitle = "PositionTitle".$UserLanguage;
        }else{
            $PositionTitle = "PositionTitleEn";
        }


        return [
            'IDPosition'                => $this->IDPosition,
            'PositionTitle'             => $this->$PositionTitle,
            'PositionReferralNumber'    => $this->PositionReferralNumber,
            'PositionReferralInterval'  => $this->PositionReferralInterval,
            'PositionLeftNumber'        => $this->PositionLeftNumber,
            'PositionRightNumber'       => $this->PositionRightNumber,
            'PositionAllNumber'         => $this->PositionAllNumber,
            'PositionNumberInterval'    => $this->PositionNumberInterval,
            'PositionLeftPoints'        => $this->PositionLeftPoints,
            'PositionRightPoints'       => $this->PositionRightPoints,
            'PositionPointInterval'     => $this->PositionPointInterval,
            'PositionAllPoints'         => $this->PositionAllPoints,
            'PositionVisits'            => $this->PositionVisits,
            'PositionVisitInterval'     => $this->PositionVisitInterval,
            'PositionChequeValue'       => $this->PositionChequeValue,
            'PositionChequeInterval'    => $this->PositionChequeInterval,
            'PositionStatus'            => $this->PositionStatus,
        ];
    }
}
