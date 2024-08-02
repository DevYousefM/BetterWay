<?php

namespace App\Http\Resources\App;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Log;

class BrandProductResource extends JsonResource
{

    public function toArray($request)
    {
        $Client = auth('client')->user();
        if ($Client) {
            $ClientLanguage = LocalAppLanguage($Client->ClientLanguage);
            $BrandProductTitle = "BrandProductTitle" . $ClientLanguage;
            $BrandProductDesc = "BrandProductDesc" . $ClientLanguage;
            $SubCategoryName = "SubCategoryName" . $ClientLanguage;
            $BrandName = "BrandName" . $ClientLanguage;
        } else {
            $ClientLanguage = $request->ClientAppLanguage == 'ar' ? 'Ar' : 'En';
            $BrandProductTitle = "BrandProductTitle" . $ClientLanguage;
            $BrandProductDesc = "BrandProductDesc" . $ClientLanguage;
            $SubCategoryName = "SubCategoryName" . $ClientLanguage;
            $BrandName = "BrandName" . $ClientLanguage;
        }

        $BrandProductDiscount = $this->BrandProductDiscount;
        if ($this->BrandProductDiscountType === "VALUE") {
            $BrandProductDiscount = round(($BrandProductDiscount / $this->BrandProductPrice) * 100);
        }

        return [
            'IDBrandProduct'                  => $this->IDBrandProduct,
            'IDBrand'                         => $this->IDBrand,
            'IDSubCategory'                   => $this->IDSubCategory,
            'BrandProductTitle'               => $this->$BrandProductTitle,
            'BrandProductDesc'                => $this->$BrandProductDesc,
            'SubCategoryName'                 => $this->$SubCategoryName,
            'BrandName'                       => $this->$BrandName,
            'BrandLogo'                       => ($this->BrandLogo) ? asset($this->BrandLogo) : '',
            'BrandRating'                     => $this->BrandRating,
            'BrandProductPrice'               => $this->BrandProductPrice,
            'BrandProductDiscount'            => $BrandProductDiscount,
            'BrandProductDiscountType'        => $this->BrandProductDiscountType,
            'BrandProductPoints'              => $this->BrandProductPoints,
            'BrandProductUplinePoints'        => $this->BrandProductUplinePoints,
            'BrandProductReferralPoints'      => $this->BrandProductReferralPoints,
            'BrandProductStartDate'           => $this->BrandProductStartDate,
            'BrandProductEndDate'             => $this->BrandProductEndDate,
            'BrandProductGallery'             => $this->BrandProductGallery,
            'ProductBranches'                 => ($this->ProductBranches) ? $this->ProductBranches : [],
        ];
    }
}
