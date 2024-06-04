<?php

namespace App\Http\Resources\Admin;

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
            'IDSocialMedia'              => $this->IDSocialMedia ,
            'SocialMediaName'            => $this->SocialMediaName ,
            'BrandSocialMediaLink'       => ($this->BrandSocialMediaLink) ? $this->BrandSocialMediaLink : '',
            'SocialMediaIcon'            => ($this->SocialMediaIcon) ? asset($this->SocialMediaIcon) : '',
            'BrandSocialMediaLinked'     => $BrandSocialMediaLinked,
        ];
    }
}
