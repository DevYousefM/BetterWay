<?php

namespace App\Http\Resources\App;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Log;

class ClientChatDetailResource extends JsonResource
{

    public function toArray($request){
        $Client = auth('client')->user();
        $Sender = "CLIENT";
        if($Client->IDClient != $this->IDSender){
            $Sender = "FRIEND";
            if($this->ClientPrivacy){
                $this->ClientPicture = Null;
            }
        }

        $Message = $this->Message;
        if($this->MessageType != "TEXT"){
            $Message = ($this->Message) ? asset($this->Message) : '';
        }


        return [
            'IDClientChatDetails'       => $this->IDClientChatDetails,
            'ClientName'                => $this->ClientName,
            'ClientPicture'             => ($this->ClientPicture) ? asset($this->ClientPicture) : '',
            'Sender'                    => $Sender,
            'Message'                   => $Message,
            'MessageType'               => $this->MessageType,
            'MessageStatus'             => $this->MessageStatus,
            'CreateDate'                => $this->created_at,
            'SeenDate'                  => $this->updated_at,
        ];
    }
}
