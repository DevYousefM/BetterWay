<?php

namespace App\Jobs;

use App\V1\Client\Client;
use App\V1\Event\Event;
use App\V1\Event\EventAttendee;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use DateTime;
use DateInterval;

class EventEnd implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    public $tries = 3;

    protected $signature = 'log:cron';

    public function __construct(){

    }

    public function handle(){
        $CurrentTime = new DateTime('now');
        $CurrentTime = $CurrentTime->format('Y-m-d H:i:s');

        Event::where("EventEndTime","<",$CurrentTime)->where("EventStatus","ONGOING")->update(["EventStatus"=>"ENDED"]);
        
        $Events = Event::where("EventStartTime","<",$CurrentTime)->where("EventStatus","ACCEPTED")->get();
        foreach($Events as $Event){
            $EventAttendees = EventAttendee::where("IDEvent",$Event->IDEvent)->where("EventAttendeeStatus","PENDING")->get();
            foreach($EventAttendees as $Attendee){
                $Client = Client::find($Attendee->IDClient);
                $BatchNumber = "#T".$Attendee->IDEventAttendee;
                $TimeFormat = new DateTime('now');
                $Time = $TimeFormat->format('H');
                $Time = $Time . $TimeFormat->format('i');
                $BatchNumber = $BatchNumber.$Time;
                $EventPoints = 0;
                $Amount = $Attendee->EventAttendeePaidAmount;
        
                AdjustLedger($Client,$Amount,0,0,0,NULL,"EVENT","WALLET","CANCELLATION",$BatchNumber);
        
                $Attendee->EventAttendeeStatus = "EXPIRED";
                $Attendee->save();
            }

            $Event->EventStatus = "ONGOING";
            $Event->save();
        }
     
    }
}
