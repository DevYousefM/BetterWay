<?php

namespace App\Http\Resources\App;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Log;

class BrandSocialMediaResource extends JsonResource
{

    public function toArray($request){
        $BrandSocialMediaLinked = $this->BrandSocialMediaLinked;
        if(!$BrandSocialMediaLinked){
            $BrandSocialMediaLinked = 0;
        }
        return [
            'SocialMediaName '            => $this->SocialMediaName ,
            'BrandSocialMediaLink '       => $this->BrandSocialMediaLink ,
            'SocialMediaIcon'             => ($this->SocialMediaIcon) ? asset($this->SocialMediaIcon) : '',
        ];
    }
}
