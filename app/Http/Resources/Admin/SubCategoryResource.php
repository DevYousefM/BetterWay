<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Log;

class SubCategoryResource extends JsonResource
{

    public function toArray($request){
        $User = auth('user')->user();
        if($User){
            $UserLanguage = AdminLanguage($User->UserLanguage);
            $CategoryName = "CategoryName".$UserLanguage;
            $SubCategoryName = "SubCategoryName".$UserLanguage;
        }else{
            $CategoryName = "CategoryNameEn";
            $SubCategoryName = "SubCategoryNameEn";
        }

        return [
            'IDSubCategory'          => $this->IDSubCategory,
            'IDCategory'             => $this->IDCategory,
            'SubCategoryName'        => $this->$SubCategoryName,
            'SubCategoryLogo'        => ($this->SubCategoryLogo) ? asset($this->SubCategoryLogo) : '',
            'CategoryName'           => $this->$CategoryName,
            'SubCategoryActive'      => $this->SubCategoryActive,
            'CreateDate'             => $this->created_at,
        ];
    }
}
