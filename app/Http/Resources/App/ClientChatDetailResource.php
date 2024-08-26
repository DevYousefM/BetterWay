<?php

namespace App\Http\Resources\App;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Log;

class ClientChatDetailResource extends JsonResource
{

    public function toArray($request)
    {
        $Message = $this->Message;
        if ($this->MessageType != "TEXT") {
            $Message = ($this->Message) ? asset($this->Message) : '';
        }


        return [
            'IDClientChatDetails'       => $this->IDClientChatDetails,
            'ClientName'                => $this->sender->ClientName,
            'ClientPicture'             => ($this->sender->ClientPicture) ? asset($this->sender->ClientPicture) : '',
            'IDSender'                  => $this->sender->IDClient,
            'Message'                   => $Message,
            'MessageType'               => $this->MessageType,
            'MessageStatus'             => $this->MessageStatus,
            'CreateDate'                => $this->created_at,
            'SeenDate'                  => $this->updated_at,
        ];
    }
}
