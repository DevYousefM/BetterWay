<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Log;

class BalanceTransferResource extends JsonResource
{

    public function toArray($request){
        if($this->MyClient){
            $MyType = "SENDER";
            $ClientName = $this->ReceiverName;
            $ClientPicture = $this->ReceiverPicture;
            $ClientPrivacy = $this->ReceiverPrivacy;
            $IDClient = $this->IDReceiver;
        }else{
            $MyType = "RECEIVER";
            $ClientName = $this->SenderName;
            $ClientPicture = $this->SenderPicture;
            $ClientPrivacy = $this->SenderPrivacy;
            $IDClient = $this->IDSender;
        }

        if($ClientPrivacy){
            $ClientPicture = Null;
        }

        return [
            'IDBalanceTransfer'        => $this->IDBalanceTransfer,
            'TransferAmount'           => $this->TransferAmount,
            'TransferStatus'           => $this->TransferStatus,
            'MyType'                   => $MyType,
            'IDClient'                 => $IDClient,
            'ClientName'               => $ClientName,
            'Date'                     => $this->created_at,
        ];
    }
}
