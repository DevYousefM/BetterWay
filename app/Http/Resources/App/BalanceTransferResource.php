<?php

namespace App\Http\Resources\App;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Log;

class BalanceTransferResource extends JsonResource
{

    public function toArray($request){
        $Client = auth('client')->user();
        if($Client->IDClient == $this->IDSender){
            $MyType = "SENDER";
            $ClientName = $this->ReceiverName;
            $ClientPicture = $this->ReceiverPicture;
            $ClientPrivacy = $this->ReceiverPrivacy;
        }else{
            $MyType = "RECEIVER";
            $ClientName = $this->SenderName;
            $ClientPicture = $this->SenderPicture;
            $ClientPrivacy = $this->SenderPrivacy;
        }

        if($ClientPrivacy){
            $ClientPicture = Null;
        }

        return [
            'IDBalanceTransfer'        => $this->IDBalanceTransfer,
            'TransferAmount'           => $this->TransferAmount,
            'TransferStatus'           => $this->TransferStatus,
            'MyType'                   => $MyType,
            'ClientName'               => $ClientName,
            'ClientPicture'            => $ClientPicture ? asset($ClientPicture) : '',
            'Date'                     => $this->created_at,
        ];
    }
}
