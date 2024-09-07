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

class EventStatusUpdate implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    public $tries = 3;

    protected $signature = 'log:cron';

    public function __construct() {}

    public function handle()
    {
        $CurrentTime = new DateTime('now');
        $CurrentTime = $CurrentTime->format('Y-m-d H:i:s');

        Event::where("EventEndTime", "<", $CurrentTime)->where("EventStatus", "ONGOING")->update(["EventStatus" => "ENDED"]);

        $today = Carbon::today()->toDateString();
        Event::whereDate("EventStartTime", $today)
            ->where("EventStatus", "ACCEPTED")
            ->update(["EventStatus" => "ONGOING"]);
    }
}
