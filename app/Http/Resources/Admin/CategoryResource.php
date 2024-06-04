<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Log;

class CategoryResource extends JsonResource
{

    public function toArray($request){
        $User = auth('user')->user();
        if($User){
            $UserLanguage = AdminLanguage($User->UserLanguage);
            $CategoryName = "CategoryName".$UserLanguage;
        }else{
            $CategoryName = "CategoryNameEn";
        }

        return [
            'IDCategory'             => $this->IDCategory,
            'CategoryName'           => $this->$CategoryName,
            'CategoryLogo'           => ($this->CategoryLogo) ? asset($this->CategoryLogo) : '',
            'CategoryType'           => $this->CategoryType,
            'CategoryGroup'          => $this->CategoryGroup,
            'CategoryActive'         => $this->CategoryActive,
            'HomeCategory'           => $this->HomeCategory,
            'CreateDate'             => $this->created_at,
        ];
    }
}
