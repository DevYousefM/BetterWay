<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Log;

class BrandProductResource extends JsonResource
{

    public function toArray($request){
        $User = auth('user')->user();
        if($User){
            $UserLanguage = AdminLanguage($User->UserLanguage);
            $BrandProductTitle = "BrandProductTitle".$UserLanguage;
            $BrandProductDesc = "BrandProductDesc".$UserLanguage;
            $SubCategoryName = "SubCategoryName".$UserLanguage;
            $BrandName = "BrandName".$UserLanguage;
        }else{
            $BrandProductTitle = "BrandProductTitleEn";
            $BrandProductDesc = "BrandProductDescEn";
            $SubCategoryName = "SubCategoryNameEn";
            $BrandName = "BrandNameEn";
        }


        return [
            'IDBrandProduct'              => $this->IDBrandProduct,
            'BrandProductTitle'           => $this->$BrandProductTitle,
            'BrandProductDesc'            => $this->$BrandProductDesc,
            'SubCategoryName'             => $this->$SubCategoryName,
            'BrandName'                   => $this->$BrandName,
            'BrandProductPrice'           => $this->BrandProductPrice,
            'BrandProductDiscount'        => $this->BrandProductDiscount,
            'BrandProductDiscountType'    => $this->BrandProductDiscountType,
            'BrandProductPoints'          => $this->BrandProductPoints,
            'BrandProductUplinePoints'    => $this->BrandProductUplinePoints,
            'BrandProductReferralPoints'  => $this->BrandProductReferralPoints,
            'BrandProductStatus'          => $this->BrandProductStatus,
            'BrandProductStartDate'       => $this->BrandProductStartDate,
            'BrandProductEndDate'         => $this->BrandProductEndDate,
            'CreateDate'                  => $this->created_at,
        ];
    }
}
