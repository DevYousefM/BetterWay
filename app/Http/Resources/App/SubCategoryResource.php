<?php

namespace App\Http\Resources\App;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Log;

class SubCategoryResource extends JsonResource
{

    public function toArray($request){
        $Client = auth('client')->user();
        if($Client){
            $ClientLanguage = LocalAppLanguage($Client->ClientLanguage);
            $SubCategoryName = "SubCategoryName".$ClientLanguage;
        }else{
            $SubCategoryName = "SubCategoryNameEn";
        }

        return [
            'IDSubCategory'             => $this->IDSubCategory,
            'SubCategoryName'           => $this->$SubCategoryName,
            'SubCategoryLogo'           => ($this->SubCategoryLogo) ? asset($this->SubCategoryLogo) : '',
        ];
    }
}
