<?php

namespace App\V1\Client;

use Illuminate\Database\Eloquent\Model;

class PositionsForClients extends Model
{
    protected $table = 'positionsforclients';
    protected $primaryKey = 'IDPositionForClient';
    protected $guarded = [];
    public function client()
    {
        return $this->belongsTo(Client::class, "IDClient");
    }
    public function position()
    {
        return $this->belongsTo(Position::class, "IDPosition");
    }
}
