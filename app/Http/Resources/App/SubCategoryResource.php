<?php

namespace App\Http\Resources\App;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Log;

class SubCategoryResource extends JsonResource
{

    public function toArray($request)
    {
        $Client = auth('client')->user();
        if ($Client) {
            $ClientLanguage = LocalAppLanguage($Client->ClientAppLanguage);
            $SubCategoryName = "SubCategoryName" . $ClientLanguage;
        } else {
            $ClientLanguage = $request->ClientAppLanguage == 'ar' ? 'Ar' : 'En';
            $SubCategoryName = "SubCategoryName" . $ClientLanguage;
        }
        return [
            'IDSubCategory'             => $this->IDSubCategory,
            'SubCategoryName'           => $this->$SubCategoryName,
            'SubCategoryLogo'           => ($this->SubCategoryLogo) ? asset($this->SubCategoryLogo) : '',
        ];
    }
}
