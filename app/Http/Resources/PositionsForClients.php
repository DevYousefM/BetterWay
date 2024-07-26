<?php

namespace App\Http\Resources;

use App\V1\Client\Position;
use Illuminate\Http\Resources\Json\JsonResource;

class PositionsForClients extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $currentPosition = $this->client->IDPosition ? Position::find($this->client->IDPosition)->PositionTitleEn : 'Networker';
        return [
            "ClientName" => $this->client->ClientName,
            'ClientPhone' => $this->client->ClientPhone,
            'ClientPosition' => $currentPosition,
            'PositionTitle' => $this->position->PositionTitleEn,
            'LeftPoints' => $this->position->PositionLeftPoints,
            'RightPoints' => $this->position->PositionRightPoints,
            'AllPoints' => $this->position->PositionAllPoints,
            'ReferralNumber' => $this->position->PositionReferralNumber,
            'LeftNumber' => $this->position->PositionLeftNumber,
            'RightNumber' => $this->position->PositionRightNumber,
            'AllNumber' => $this->position->PositionAllNumber,
            'Visits' => $this->position->PositionVisits,
            'ChequeValue' => $this->position->PositionChequeValue,
        ];
    }
}
