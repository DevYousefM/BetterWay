<?php

namespace App\Console\Commands;

use App\Notification;
use App\V1\Client\Client;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ContractNotifications extends Command
{
    protected $signature = 'send:contract-notifications';

    protected $description = 'Send notification to users who registered 14 days ago';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        try {
            // Log start of the process
            // Log::info("Notification process started.");
            // Query to fetch users who need notifications
            $users = Client::whereDate('created_at', '>=', Carbon::now()->subDays(14)->toDateString())
                ->whereDate('created_at', '<=', Carbon::now()->subDays(10)->toDateString())
                ->whereDoesntHave('clientdocuments', function ($query) {
                    $query->where('ClientDocumentType', 'CONTRACT');
                })
                ->where('ClientStatus', 'ACTIVE')
                ->get();
            // Log::info("Users fetched: " . $users->count());

            // Extract device tokens for Firebase notifications
            $firebaseTokens = $users->pluck('ClientDeviceToken')->toArray();
            // Log::info("Firebase tokens: " . json_encode($firebaseTokens));

            $SERVER_API_KEY = env('FCM_SERVER_API_KEY');
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

            // Log::info("Notification data prepared: " . $dataString);

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
            // Log::info("cURL response: " . $response);

            // Check if cURL request was successful
            if ($response === false) {
                $error = curl_error($ch);
                curl_close($ch);
                Log::error("Curl Error: " . $error);
                throw new \Exception("Curl Error: " . $error);
            }

            // Decode the JSON response
            $responseData = json_decode($response, true);
            // Log::info("Decoded response: " . json_encode($responseData));

            // Handle response from FCM
            if (isset($responseData['results'])) {
                foreach ($responseData['results'] as $key => $result) {
                    if (isset($result['message_id'])) {
                        // Notification sent successfully
                        $user = $users[$key];
                        // Log::info("Notification sent to user ID: " . $user->IDClient);

                        // Store notification in database
                        Notification::create([
                            'client_id' => $user->IDClient,
                            'title' => $title,
                            'body' => $body,
                        ]);
                    } elseif (isset($result['error']) && $result['error'] === 'NotRegistered') {
                        // Handle "NotRegistered" error - remove token from your database or list
                        $invalidToken = $firebaseTokens[$key];
                        Log::warning("NotRegistered error for token: " . $invalidToken);
                        Client::where('ClientDeviceToken', $invalidToken)->update(['ClientDeviceToken' => null]);
                    }
                }
            }

            curl_close($ch);
            // Log::info("Notification process completed.");
        } catch (\Exception $e) {
            Log::error("Exception occurred: " . $e->getMessage());
            return response()->json(['error' => 'Something went wrong.'], 500);
        }
    }
}
