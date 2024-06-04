<?php

namespace App\Http\Resources\App;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Log;
use App\V1\Client\ClientChatDetail;

class ClientChatResource extends JsonResource
{

    public function toArray($request){
        $Client = auth('client')->user();
        if($Client->IDClient == $this->IDClient){
            $FriendName = $this->FriendName;
            $IDFriend = $this->IDFriend;
            $FriendPicture = $this->FriendPicture;
            if($this->FriendPrivacy){
                $FriendPicture = Null;
            }
        }else{
            $FriendName = $this->ClientName;
            $IDFriend = $this->IDClient;
            $FriendPicture = $this->ClientPicture;
            if($this->ClientPrivacy){
                $FriendPicture = Null;
            }
        }

        $LastMessage = "";
        $MessagesNumber = 0;
        $ClientChatDetail = ClientChatDetail::where("IDClientChat",$this->IDClientChat)->orderby("IDClientChatDetails","DESC")->first();
        if($ClientChatDetail){
            $LastMessage = $ClientChatDetail->Message;
            $MessagesNumber = ClientChatDetail::where("IDClientChat",$this->IDClientChat)->where("IDSender","<>",$Client->IDClient)->where("MessageStatus","SENT")->count();
        }


        return [
            'IDClientChat'              => $this->IDClientChat,
            'IDFriend'                  => $IDFriend,
            'FriendName'                => $FriendName,
            'FriendPicture'             => ($FriendPicture) ? asset($FriendPicture) : '',
            'LastMessage'               => $LastMessage,
            'MessagesNumber'            => $MessagesNumber,
            'CreateDate'                => $this->created_at,
            'LastMessageDate'           => $this->updated_at,
        ];
    }
}
