<?php

namespace App\V1\Client;

use Illuminate\Database\Eloquent\Model;

class ClientDocument extends Model
{
    protected $table = 'clientdocuments';
    protected $primaryKey = 'IDClientDocument';
}
