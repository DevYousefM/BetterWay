<?php

namespace App\V1\User;

use App\V1\Client\Client;
use Illuminate\Database\Eloquent\Model;

class ActionBackLog extends Model
{
    protected $table = 'actionbacklog';
    protected $primaryKey = 'IDActionBackLog';

    public function client() {
        return $this->belongsTo(Client::class, 'IDLink', 'IDClient');
    }
}
