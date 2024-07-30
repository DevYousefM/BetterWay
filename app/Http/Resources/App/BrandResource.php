<?php

namespace App\Http\Resources\App;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Log;

class BrandResource extends JsonResource
{

    public function toArray($request)
    {
        $ClientLanguage = LocalAppLanguage();
        $BrandName = "BrandName" . $ClientLanguage;
        $BrandDesc = "BrandDesc" . $ClientLanguage;
        $BrandPolicy = "BrandPolicy" . $ClientLanguage;

        return [
            'IDBrand'             => $this->IDBrand,
            'BrandName'           => $this->$BrandName,
            'BrandDesc'           => $this->$BrandDesc ?? '',
            'BrandPolicy'         => $this->$BrandPolicy ?? '',
            'BrandLogo'           => ($this->BrandLogo) ? asset($this->BrandLogo) : '',
            'BrandNumber'         => $this->BrandNumber,
            'BrandRating'         => $this->BrandRating,
        ];
    }
}
