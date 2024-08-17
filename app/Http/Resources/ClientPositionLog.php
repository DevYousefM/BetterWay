<?php

namespace App\Http\Resources;

use App\V1\User\ActionBackLog;
use Illuminate\Http\Resources\Json\JsonResource;

class ClientPositionLog extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        return [
            'Position' => $this->Position ?? $this->ActionBackLogDesc,
            'Date' => $this->Date ?? $this->created_at->format('Y-m-d'),
        ];
    }
}
