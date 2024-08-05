<?php

namespace App\Http\Resources\App;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Log;
use App\V1\Event\EventAttendee;

class EventResource extends JsonResource
{

    public function toArray($request)
    {
        $Client = auth('client')->user();
        $EventAttendeeStatus = "NONE";
        $EventAttendeePaidAmount = 0;
        if ($Client) {
            $ClientLanguage = LocalAppLanguage($Client->ClientAppLanguage);
            $AreaName = "AreaName" . $ClientLanguage;
            $CityName = "CityName" . $ClientLanguage;
            $EventTitle = "EventTitle" . $ClientLanguage;
            $EventDesc = "EventDesc" . $ClientLanguage;
            $EventPolicy = "EventPolicy" . $ClientLanguage;
            $EventAttendee = EventAttendee::where("IDEvent", $this->IDEvent)->where("IDClient", $Client->IDClient)->first();
            if ($EventAttendee) {
                $EventAttendeeStatus = $EventAttendee->EventAttendeeStatus;
                $EventAttendeePaidAmount = $EventAttendee->EventAttendeePaidAmount;
            }
        } else {
            $ClientLanguage = $request->ClientAppLanguage == "ar" ? "Ar" : "En";
            $EventTitle = "EventTitle" . $ClientLanguage;
            $EventDesc = "EventDesc" . $ClientLanguage;
            $AreaName = "AreaName" . $ClientLanguage;
            $CityName = "CityName" . $ClientLanguage;
            $EventPolicy = "EventPolicy" . $ClientLanguage;;
        }

        return [
            'IDEvent'                   => $this->IDEvent,
            'EventTitle'                => $this->$EventTitle,
            'EventDesc'                 => $this->$EventDesc,
            'EventPolicy'               =>  $this->$EventPolicy ? $this->$EventPolicy : "",
            'CityName'                  => $this->$CityName,
            'AreaName'                  => $this->$AreaName,
            'EventPrice'                => $this->EventPrice,
            'EventStartTime'            => $this->EventStartTime,
            'EventEndTime'              => $this->EventEndTime,
            'EventLatitude'             => $this->EventLatitude,
            'EventLongitude'            => $this->EventLongitude,
            'EventAddress'              => $this->EventAddress,
            'EventClientNumber'         => $this->EventClientNumber,
            'EventAvailableNumber'      => $this->EventMaxNumber ? $this->EventMaxNumber - $this->EventClientNumber : $this->EventMaxNumber,
            'EventStatus'               => $this->EventStatus,
            'EventAttendeeStatus'       => $EventAttendeeStatus,
            'EventAttendeePaidAmount'   => $EventAttendeePaidAmount,
            'EventGallery'              => $this->EventGallery,
        ];
    }
}
