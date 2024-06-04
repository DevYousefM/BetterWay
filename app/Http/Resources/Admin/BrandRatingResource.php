<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Log;

class BrandRatingResource extends JsonResource
{

    public function toArray($request){
        $User = auth('user')->user();
        if($User){
            $UserLanguage = AdminLanguage($User->UserLanguage);
            $BrandName = "BrandName".$UserLanguage;
        }else{
            $BrandName = "BrandNameEn";
        }


        return [
            'IDBrandRating'              => $this->IDBrandRating,
            'IDBrand'                    => $this->IDBrand,
            'BrandName'                  => $this->$BrandName,
            'BrandRating'                => $this->BrandRating,
            'BrandReview'                => $this->BrandReview,
            'BrandRatingStatus'          => $this->BrandRatingStatus,
            'ClientName'                 => $this->ClientName,
            'ClientPhone'                => $this->ClientPhone,
            'CreateDate'                 => $this->created_at,
        ];
    }
}
