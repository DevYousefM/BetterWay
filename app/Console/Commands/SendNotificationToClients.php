<?php

namespace App\Console\Commands;

use App\V1\Client\Client;
use App\V1\Plan\Bonanza;
use Illuminate\Console\Command;

class SendNotificationToClients extends Command
{
    protected $signature = 'notify:clients {bonanza_id}';
    protected $description = 'Send notifications to all clients about a new Bonanza';

    protected $bonanza_id;

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $bonanza_id = $this->argument('bonanza_id');

        $clients = Client::all();
        $bonanza = Bonanza::find($bonanza_id);

        foreach ($clients as $client) {
            $dataPayload = [
                "Bonanza" => $bonanza,
                "message" => "new bonanza",
            ];

            $title = "new bonanza";
            $body = "There is a new Bonanza";
            sendFirebaseNotification($client, $dataPayload, $title, $body);
        }

        $this->info('Notifications sent successfully!');
    }
}
