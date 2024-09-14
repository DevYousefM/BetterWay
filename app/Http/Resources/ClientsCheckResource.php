<?php

namespace App\Http\Resources;

use App\V1\Client\Client;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ClientsCheckResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $ClientPhone = $this->ClientPhone;
        if ($this->ClientType == "Agency") {
            $Client = Client::find($this->AgencyFor);
            if ($Client) {
                $ClientPhone = $Client->ClientPhone;
            }
        }

        return [
            "IDClient" => $this->IDClient,
            "ClientName" => $this->ClientName,
            "ClientPicture" => $this->ClientPicture,
            "ClientPhone" => $ClientPhone,
            "ClientAppID" => $this->ClientAppID,
            "ClientPrivacy" => $this->ClientPrivacy
        ];
    }
}
