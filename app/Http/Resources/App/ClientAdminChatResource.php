<?php

namespace App\Http\Resources\App;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Log;
use App\V1\Client\ClientAdminChatDetails;

class ClientAdminChatResource extends JsonResource
{

    public function toArray($request)
    {
        $Client = auth('client')->user();

        $LastMessage = "";
        $MessagesNumber = 0;
        $ClientAdminChatDetails = ClientAdminChatDetails::where("IDClientAdminChat", $this->IDClientAdminChat)->orderby("IDClientAdminChatDetails", "DESC")->first();
        if ($ClientAdminChatDetails) {
            $LastMessage = $ClientAdminChatDetails->Message;
            $MessagesNumber = ClientAdminChatDetails::where("IDClientAdminChat", $this->IDClientAdminChat)->where("MessageStatus", "SENT")->count();
        }

        return [
            'IDClientAdminChat'         => $this->IDClientAdminChat,
            'LastMessage'               => $LastMessage,
            'MessagesNumber'            => $MessagesNumber,
            'CreateDate'                => $this->created_at,
            'LastMessageDate'           => $this->updated_at,
        ];
    }
}
