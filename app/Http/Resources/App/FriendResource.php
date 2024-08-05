<?php

namespace App\Http\Resources\App;

use App\V1\General\Nationality;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Log;

class FriendResource extends JsonResource
{

    public function toArray($request)
    {
        $Client = auth('client')->user();
        $ClientAppLanguage = LocalAppLanguage($Client->ClientAppLanguage);
        if ($Client) {
            $AreaName = "AreaName" . $ClientAppLanguage;
            $CityName = "CityName" . $ClientAppLanguage;
        } else {
            $AreaName = "AreaNameEn";
            $CityName = "CityNameEn";
        }

        $ClientPicture = $this->ClientPicture;
        $ClientPhone = $this->ClientPhone;
        if ($this->ClientPrivacy) {
            $ClientPicture = Null;
            $ClientPhone = Null;
        }

        $PositionName = $this->PositionName;
        if (!$PositionName) {
            $PositionName = "Networker";
            if ($ClientAppLanguage == "Ar") {
                $PositionName = "Networker";
            }
        }

        $Nationality = Nationality::find($this->IDNationality);
        $ClientNationality = "NationalityName" . $ClientAppLanguage;
        $ClientNationality = $Nationality->$ClientNationality;

        return [
            'IDClient'            => $this->IDClient,
            'ClientName'          => $this->ClientName,
            'ClientAppID'         => $this->ClientAppID,
            'ClientPhone'         => $ClientPhone ? $ClientPhone : '',
            'ClientPicture'       => $ClientPicture ? asset($ClientPicture) : '',
            'CityName'            => $this->$CityName,
            'AreaName'            => $this->$AreaName,
            'ClientBirthDate'     => $this->ClientBirthDate,
            'ClientNationalID'    => $this->ClientNationalID,
            'ClientNationality'   => $ClientNationality,
            'ClientGender'        => $this->ClientGender,
            'ClientTotalPoints'   => $this->ClientLeftPoints + $this->ClientRightPoints,
            'ClientLeftPoints'    => $this->ClientLeftPoints,
            'ClientRightPoints'   => $this->ClientRightPoints,
            'ClientLeftNumber'    => $this->ClientLeftNumber,
            'ClientRightNumber'   => $this->ClientRightNumber,
            'PositionName'        => $PositionName,
            'ClientFriendStatus'  => $this->ClientFriendStatus,
            'ClientImages'        => $this->ClientImages,
            'ClientVideos'        => $this->ClientVideos,
        ];
    }
}
