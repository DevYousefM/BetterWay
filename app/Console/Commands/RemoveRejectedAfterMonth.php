<?php

namespace App\Console\Commands;

use App\V1\Client\PositionsForClients;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class RemoveRejectedAfterMonth extends Command
{

    protected $signature = 'remove:rejected-after-month';

    protected $description = 'Remove Rejected Clients after 30 days';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {

        $PositionsForClients = PositionsForClients::whereDate('created_at', '<=', Carbon::now()->subDays(30)->toDateString())
            ->where("Status", "REJECTED");


        $PositionsForClients->delete();

        return 0;
    }
}
