<?php

namespace App\Http\Resources\App;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Log;

class AdvertisementResource extends JsonResource
{

    public function toArray($request){
        return [
            'IDAdvertisement '              => $this->IDAdvertisement ,
            'IDLink '                       => $this->IDLink ,
            'AdvertisementImage'            => ($this->AdvertisementImage) ? asset($this->AdvertisementImage) : '',
            'AdvertisementService'          => $this->AdvertisementService,
            'AdvertisementLocation'         => $this->AdvertisementLocation,
        ];
    }
}
