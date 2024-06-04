<?php

namespace App\Http\Resources\App;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Log;

class ClientBrandProductResource extends JsonResource
{

    public function toArray($request){
        $Client = auth('client')->user();
        if($Client){
            $ClientLanguage = LocalAppLanguage($Client->ClientLanguage);
            $BrandProductTitle = "BrandProductTitle".$ClientLanguage;
            $BrandProductDesc = "BrandProductDesc".$ClientLanguage;
            $SubCategoryName = "SubCategoryName".$ClientLanguage;
            $BrandName = "BrandName".$ClientLanguage;
        }else{
            $BrandProductTitle = "BrandProductTitleEn";
            $BrandProductDesc = "BrandProductDescEn";
            $SubCategoryName = "SubCategoryNameEn";
            $BrandName = "BrandNameEn";
        }


        return [
            'IDBrandProduct'             => $this->IDBrandProduct,
            'IDBrand'                    => $this->IDBrand,
            'IDSubCategory'              => $this->IDSubCategory,
            'BrandProductTitle'          => $this->$BrandProductTitle,
            'BrandProductDesc'           => $this->$BrandProductDesc,
            'SubCategoryName'            => $this->$SubCategoryName,
            'BrandName'                  => $this->$BrandName,
            'BrandLogo'                  => ($this->BrandLogo) ? asset($this->BrandLogo) : '',
            'BrandRating'                => $this->BrandRating,
            'BrandProductPrice'          => $this->BrandProductPrice,
            'BrandProductDiscount'       => $this->BrandProductDiscount,
            'BrandProductPoints'         => $this->BrandProductPoints,
            'BrandProductStartDate'      => $this->BrandProductStartDate,
            'BrandProductEndDate'        => $this->BrandProductEndDate,
            'BrandProductGallery'        => $this->BrandProductGallery,
            'IDClientBrandProduct'       => $this->IDClientBrandProduct,
            'ClientBrandProductSerial'   => $this->ClientBrandProductSerial,
            'ClientBrandProductStatus'   => $this->ClientBrandProductStatus,
            'CreateDate'                 => $this->created_at,
            'StatusDate'                 => $this->updated_at,
        ];
    }
}
