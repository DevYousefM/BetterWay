<?php

namespace App\V1\Event;

use Illuminate\Database\Eloquent\Model;

class EventAttendee extends Model
{
    protected $table = 'eventattendees';
    protected $primaryKey = 'IDEventAttendee';
}
