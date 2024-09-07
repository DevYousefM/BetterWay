<?php

namespace App\Console\Commands;

use App\Jobs\EventStatusUpdate as JobsEventStatusUpdate;
use Illuminate\Console\Command;

class EventStatusUpdate extends Command
{
    protected $signature = 'app:event-status-update';

    protected $description = 'Command description';

    public function handle()
    {
        JobsEventStatusUpdate::dispatch();
    }
}
