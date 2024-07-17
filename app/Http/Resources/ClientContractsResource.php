<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ClientContractsResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            "IDClientDocument" => $this->IDClientDocument,
            "IDClient" => $this->IDClient,
            "ClientDocumentPath" => asset("uploads/" . $this->ClientDocumentPath),
            "ClientDocumentType" => $this->ClientDocumentType,
            "created_at" => $this->created_at,
        ];
    }
}
