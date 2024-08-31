<?php

namespace App\Console\Commands;

use App\V1\Client\Client;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class GetUsersRegistered14DaysAgo extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'client:get-registered-14-days-ago';

    protected $description = 'get registered 14 days ago';

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $clientsSecondSignup = Client::whereDate('created_at', '<=', Carbon::now()->subDays(14))->where("ClientNationalID", null)->where("ClientPassport", null)->get();
        foreach ($clientsSecondSignup as $client) {
            $client->ClientStatus = "NOT_VERIFIED";
            $client->save();
        }

        $clientsWithoutContract = Client::whereDate('created_at', '<=', Carbon::now()->subDays(14))->whereDoesntHave('clientdocuments', function ($query) {
            $query->where('ClientDocumentType', 'CONTRACT');
        })->where("IDClient", "<>", 1)
            ->get();

        foreach ($clientsWithoutContract as $client) {
            $client->ClientStatus = "NOT_VERIFIED";
            $client->save();
        }
        return 0;
    }
}
