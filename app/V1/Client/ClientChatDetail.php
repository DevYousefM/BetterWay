<?php

namespace App\V1\Client;

use Illuminate\Database\Eloquent\Model;

class ClientChatDetail extends Model
{
    protected $table = 'clientchatdetails';
    protected $primaryKey = 'IDClientChatDetails';

    public function sender() {
        return $this->belongsTo(Client::class, 'IDSender', 'IDClient');
    }
}
