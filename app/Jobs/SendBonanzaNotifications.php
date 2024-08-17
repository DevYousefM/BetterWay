<?php

namespace App\Jobs;

use App\V1\Client\Client;
use App\V1\Plan\Bonanza;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendBonanzaNotifications implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;


    
    protected $bonanza_id;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($bonanza_id)
    {
        $this->bonanza_id = $bonanza_id;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $clients = Client::all();
        $bonanza = Bonanza::find($this->bonanza_id);

        foreach ($clients as $client) {
            $dataPayload = [
                "Bonanza" => $bonanza,
                "message" => "new bonanza",
            ];

            $title = "new bonanza";
            $body = "There is a new Bonanza";
            sendFirebaseNotification($client, $dataPayload, $title, $body);
        }
    }
}
