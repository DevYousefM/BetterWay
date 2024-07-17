<?php

namespace App\Console\Commands;

use App\Notification;
use App\V1\Client\Client;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SendNotifications extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'send:notifications';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send notification to users who registered 14 days ago';

    /**
     * Create a new command instance.
     *
     * @return void
     */
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
        Log::info("Notification Started");

        try {
            // Query to fetch users who need notifications
            $users = Client::whereDate('created_at', '<=', Carbon::now()->subDays(15)->toDateString())
                ->whereDoesntHave('clientdocuments', function ($query) {
                    $query->where('ClientDocumentType', 'CONTRACT');
                })
                ->whereDate('created_at', '>=', Carbon::now()->subDays(20)->toDateString())
                ->get();
            // Extract device tokens for Firebase notifications
            $firebaseTokens = $users->pluck('ClientDeviceToken')->toArray();
            $SERVER_API_KEY = env('FCM_SERVER_KEY');
            $body = 'Please send the contract before 14 days from your registration date.';
            $title = 'Reminder';
            $data = [
                "registration_ids" => $firebaseTokens,
                "notification" => [
                    "title" => $title,
                    "body" => $body,
                ]
            ];
            $dataString = json_encode($data);
            // Setup headers for the request

            $headers = [
                'Authorization: key=' . $SERVER_API_KEY,
                'Content-Type: application/json',
            ];

            // Initialize cURL session
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, 'https://fcm.googleapis.com/fcm/send');
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $dataString);

            // Execute the cURL session
            $response = curl_exec($ch);

            // Check if cURL request was successful
            if ($response === false) {
                $error = curl_error($ch);
                curl_close($ch);
                Log::error("Curl Error: " . $error);
                throw new \Exception("Curl Error: " . $error);
            }

            // Decode the JSON response
            $responseData = json_decode($response, true);

            // Handle response from FCM
            if (isset($responseData['results'])) {
                foreach ($responseData['results'] as $key => $result) {
                    if (isset($result['message_id'])) {
                        // Notification sent successfully
                        $user = $users[$key];
                        Log::info("UserID: " . $user->IDClient);
                        // Store notification in database
                        Notification::create([
                            'client_id' => $user->IDClient,
                            'title' => $title,
                            'body' => $body,
                        ]);
                    } elseif (isset($result['error']) && $result['error'] === 'NotRegistered') {
                        Log::info("ERROR: NotRegistered");
                        // Handle "NotRegistered" error - remove token from your database or list
                        $invalidToken = $firebaseTokens[$key];
                        Client::where('ClientDeviceToken', $invalidToken)->update(['ClientDeviceToken' => null]);
                    }
                }
            }

            curl_close($ch);
            Log::info("Done");
        } catch (\Exception $e) {
            Log::error("Exception occurred: " . $e->getMessage());
            return response()->json(['error' => 'Something went wrong.'], 500);
        }
    }
}
