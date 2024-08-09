<?php

namespace App\Http\Resources;

use App\V1\Client\Position;
use Illuminate\Http\Resources\Json\JsonResource;

class PositionsForClients extends JsonResource
{
    
    public function toArray($request)
    {
        $currentPosition = $this->client->IDPosition ? Position::find($this->client->IDPosition)->PositionTitleEn : 'Networker';
        return [
            "ID" => $this->IDPositionForClient,
            "IDClient" => $this->client->IDClient,
            "IDPosition" => $this->client->IDPosition,
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
            'Date' => $this->created_at->format('Y-m-d'),
        ];
    }
}
