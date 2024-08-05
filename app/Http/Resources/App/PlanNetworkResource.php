<?php

namespace App\Http\Resources\App;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Log;

class PlanNetworkResource extends JsonResource
{

    public function toArray($request)
    {
        $Client = auth('client')->user();
        $ClientLanguage = LocalAppLanguage($Client->ClientAppLanguage);
        $ClientPrivacy = $this->ClientPrivacy;
        $ClientContact = $this->ClientPhone;
        $ClientPicture = $this->ClientPicture;
        if ($ClientPrivacy) {
            $ClientContact = $this->ClientAppID;
            $ClientPicture = Null;
        }
        $PositionName = "Networker";
        if ($ClientLanguage == "Ar") {
            $PositionName = "Networker";
        }


        return [
            'IDClient'             => $this->IDClient,
            'ClientName'           => $this->ClientName,
            'ClientContact'        => $ClientContact,
            'ClientPicture'        => ($ClientPicture) ? asset($ClientPicture) : '',
            'ReferralName'         => ($this->ReferralName) ? $this->ReferralName : '',
            'TotalPoints'          => $this->ClientLeftPoints + $this->ClientRightPoints,
            'LeftPoints'           => $this->ClientLeftPoints,
            'RightPoints'          => $this->ClientRightPoints,
            'NetworkPosition'      => $this->PlanNetworkPosition,
            'PositionName'         => ($this->PositionName) ? $this->PositionName : $PositionName,
            'PlanNetworkAgencies'  => ($this->PlanNetworkAgencies) ? $this->PlanNetworkAgencies : [],
            'ChildrenNetwork'      => ($this->ChildrenNetwork) ? $this->ChildrenNetwork : [],
        ];
    }
}
