<?php

namespace App\V1\Client;

use Illuminate\Database\Eloquent\Model;

class ClientPointsLedger extends Model
{
    protected $table = 'clientpointsledger';
    protected $primaryKey = 'IDClientPointsLedger';

    public function client()
    {
        return $this->belongsTo(Client::class, "IDClient");
    }
}
