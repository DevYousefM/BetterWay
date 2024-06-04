<?php

namespace App\V1\Event;

use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    protected $table = 'events';
    protected $primaryKey = 'IDEvent';
}
