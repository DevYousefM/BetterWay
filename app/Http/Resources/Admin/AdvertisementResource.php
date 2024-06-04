<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Log;

class AdvertisementResource extends JsonResource
{

    public function toArray($request){
        return [
            'IDAdvertisement'              => $this->IDAdvertisement ,
            'IDLink'                       => $this->IDLink ,
            'AdvertisementStartDate'       => $this->AdvertisementStartDate ,
            'AdvertisementEndDate'         => $this->AdvertisementEndDate ,
            'AdvertisementImage'            => ($this->AdvertisementImage) ? asset($this->AdvertisementImage) : '',
            'AdvertisementService'          => $this->AdvertisementService,
            'AdvertisementLocation'         => $this->AdvertisementLocation,
            'AdvertisementActive'           => $this->AdvertisementActive,
            'CreateDate'                    => $this->created_at,
        ];
    }
}
