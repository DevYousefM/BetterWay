<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Log;

class PlanResource extends JsonResource
{

    public function toArray($request){
        $User = auth('user')->user();
        if($User){
            $UserLanguage = AdminLanguage($User->UserLanguage);
            $PlanName = "PlanName".$UserLanguage;
            $PlanDesc = "PlanDesc".$UserLanguage;
        }else{
            $PlanName = "PlanNameEn";
            $PlanDesc = "PlanDescEn";
        }

        return [
            'IDPlan'                => $this->IDPlan,
            'PlanName'              => $this->$PlanName,
            'PlanDesc'              => $this->$PlanDesc,
            'PlanStatus'            => $this->PlanStatus,
            'LeftBalanceNumber'     => $this->LeftBalanceNumber,
            'RightBalanceNumber'    => $this->RightBalanceNumber,
            'ChequeValue'           => $this->ChequeValue,
            'LeftMaxOutNumber'      => $this->LeftMaxOutNumber,
            'RightMaxOutNumber'     => $this->RightMaxOutNumber,
            'ChequeMaxOut'          => $this->ChequeMaxOut,
            'ChequeEarnDay'         => $this->ChequeEarnDay,
            'CreateDate'            => $this->created_at,
        ];
    }
}