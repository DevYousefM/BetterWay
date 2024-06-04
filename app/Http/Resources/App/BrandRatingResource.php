<?php

namespace App\Http\Resources\App;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Log;

class BrandRatingResource extends JsonResource
{

    public function toArray($request){
        return [
            'ClientName'             => $this->ClientName,
            'ClientPicture'          => ($this->ClientPicture) ? asset($this->ClientPicture) : '',
            'BrandRating'            => $this->BrandRating,
            'BrandReview'            => ($this->BrandReview) ? $this->BrandReview : '',
            'Date'                   => $this->created_at,
        ];
    }
}
