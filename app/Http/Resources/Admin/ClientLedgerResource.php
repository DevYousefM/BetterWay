<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Log;

class ClientLedgerResource extends JsonResource
{

    public function toArray($request){
        $ClientLedgerAmount = $this->ClientLedgerFinalBalance - $this->ClientLedgerInitialeBalance;
        $ClientLedgerPoints = $this->ClientLedgerFinalPoints - $this->ClientLedgerInitialePoints;

        return [
            'IDClientLedger'               => $this->IDClientLedger,
            'ClientLedgerAmount'           => $ClientLedgerAmount,
            'ClientLedgerPoints'           => $ClientLedgerPoints,
            'ClientLedgerSource'           => $this->ClientLedgerSource,
            'ClientLedgerDestination'      => $this->ClientLedgerDestination,
            'ClientLedgerInitialeBalance'  => $this->ClientLedgerInitialeBalance,
            'ClientLedgerFinalBalance'     => $this->ClientLedgerFinalBalance,
            'ClientLedgerInitialePoints'   => $this->ClientLedgerInitialePoints,
            'ClientLedgerFinalPoints'      => $this->ClientLedgerFinalPoints,
            'ClientLedgerType'             => $this->ClientLedgerType,
            'ClientLedgerBatchNumber'      => $this->ClientLedgerBatchNumber,
            'Date'                         => $this->created_at,
        ];
    }
}
