<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Log;
use App\V1\Client\Client;
use App\V1\General\Nationality;
use App\V1\Plan\Plan;
use App\V1\Plan\PlanNetwork;

class ClientAgencyResource extends JsonResource
{

    public function toArray($request)
    {
        $User = auth('user')->user();
        if ($User) {
            $UserLanguage = AdminLanguage($User->UserLanguage);
            $AreaName = "AreaName" . $UserLanguage;
            $CityName = "CityName" . $UserLanguage;
            $PlanName = "PlanName" . $UserLanguage;
            $PositionTitle = "PositionTitle" . $UserLanguage;
        }

        $ClientPicture = $this->ClientPicture;
        if ($this->ClientPrivacy && $User->IDRole != 1) {
            $ClientPicture = Null;
        }

        $ReferralNumber = PlanNetwork::where("IDReferralClient", $this->IDClient)->count();
        $Nationality = Nationality::find($this->IDNationality);
        $ClientAppLanguage = LocalAppLanguage($User->UserLanguage);
        $ClientNationality = "NationalityName" . $ClientAppLanguage;
        $ClientNationality = $Nationality->$ClientNationality;

        return [
            'IDClient'               => $this->IDClient,
            'ClientName'             => $this->ClientName,
            'ClientNameArabic'       => $this->ClientNameArabic,
            'ClientEmail'            => $this->ClientEmail,
            'ClientPhone'            => $this->ClientPhone,
            'ClientSecondPhone'      => $this->ClientSecondPhone,
            'ClientAppID'            => $this->ClientAppID,
            'ClientStatus'           => $this->ClientStatus,
            'ClientDeleted'          => $this->ClientDeleted,
            'ClientPicture'          => ($ClientPicture) ? asset($ClientPicture) : '',
            'ClientBalance'          => $this->ClientBalance,
            'ClientGender'           => $this->ClientGender,
            'ClientBirthDate'        => $this->ClientBirthDate,
            'ClientNationalID'       => $this->ClientNationalID,
            'ClientRewardPoints'     => $this->ClientRewardPoints,
            'ClientRightPoints'      => $this->ClientRightPoints,
            'ClientLeftPoints'       => $this->ClientLeftPoints,
            'ClientTotalPoints'      => $this->ClientRightPoints + $this->ClientLeftPoints,
            'ClientRightNumber'      => $this->ClientRightNumber,
            'ClientLeftNumber'       => $this->ClientLeftNumber,
            'ClientTotalNumber'      => $this->ClientRightNumber + $this->ClientLeftNumber,
            'ReferralNumber'         => $ReferralNumber,
            'ClientPrivacy'          => $this->ClientPrivacy,
            'ClientPassport'         => $this->ClientPassport,
            'ClientCurrentAddress'   => $this->ClientCurrentAddress,
            'ClientIDAddress'        => $this->ClientIDAddress,
            'ClientNationality'      => $ClientNationality,
            'ContractCompleted'      => $this->ClientContractCompleted,
            'PositionTitle'          => ($this->$PositionTitle) ? $this->$PositionTitle : '',
            'CityName'               => $this->$CityName,
            'AreaName'               => $this->$AreaName,
            'PlanName'               => ($this->$PlanName) ? $this->$PlanName : '',
            'IDParentClient'         => $this->IDParentClient,
            'ParentClient'           => ($this->ParentName) ? $this->ParentName : '',
            'ParentPhone'            => ($this->ParentPhone) ? $this->ParentPhone : '',
            'IDReferralClient'       => $this->IDReferralClient,
            'ReferralClient'         => ($this->ReferralName) ? $this->ReferralName : '',
            'ReferralPhone'          => ($this->ReferralPhone) ? $this->ReferralPhone : '',
            'ClientDocuments'        => $this->ClientDocuments ? $this->ClientDocuments : [],
            'ClientGallery'          => $this->ClientGallery ? $this->ClientGallery : [],
            'RegisterDate'           => $this->created_at,
        ];
    }
}
