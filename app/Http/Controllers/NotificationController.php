<?php

namespace App\Http\Controllers;

use App\Notification;
use App\V1\Client\Client;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class NotificationController extends Controller
{

    public function sendNotifications()
    {

        $users = Client::whereDate('created_at', '>=', Carbon::now()->subDays(14)->toDateString())->get();

        $firebaseTokens = $users->pluck('ClientDeviceToken')->toArray();
        $SERVER_API_KEY = env('FCM_SERVER_KEY');
        $body = '15 days have passed since your registration.';
        $title = 'Reminder';
        $data = [
            "registration_ids" => $firebaseTokens,
            "notification" => [
                "title" => $title,
                "body" => $body,
            ]
        ];
        $dataString = json_encode($data);

        $headers = [
            'Authorization: key=' . $SERVER_API_KEY,
            'Content-Type: application/json',
        ];

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, 'https://fcm.googleapis.com/fcm/send');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $dataString);
        $response = curl_exec($ch);

        if ($response === false) {
            $error = curl_error($ch);
            curl_close($ch);
            return response()->json(['error' => $error], 500);
        }

        $responseData = json_decode($response, true);

        // Handle response from FCM and store only successful notifications
        if (isset($responseData['results'])) {
            foreach ($responseData['results'] as $key => $result) {
                if (isset($result['message_id'])) {
                    // Notification sent successfully
                    $user = $users[$key];
                    Notification::create([
                        'client_id' => $user->IDClient,
                        'title' => $title,
                        'body' => $body,
                    ]);
                } elseif (isset($result['error']) && $result['error'] === 'NotRegistered') {
                    // Handle "NotRegistered" error - remove token from your database or list
                    $invalidToken = $firebaseTokens[$key];
                    Client::where('ClientDeviceToken', $invalidToken)->update(['ClientDeviceToken' => null]);
                }
            }
        }

        curl_close($ch);

        return response()->json(['response' => $responseData], 200);
    }


    public function allNoification($client_id)
    {
        $notifications = Notification::where('client_id', $client_id)
            ->orderBy('created_at', 'desc')
            ->get()->map(function ($notification) {
                return [
                    'id' => $notification->id,
                    'client_id' => $notification->client_id,
                    'title' => $notification->title,
                    'body' => $notification->body,
                    'created_at' => $notification->created_at->format('Y-m-d H:i:s'),
                    'updated_at' => $notification->updated_at->format('Y-m-d H:i:s'),
                ];
            });

        return response()->json(['notifications' => $notifications, 'message' => 'all Noification returned successfully'], 200);
    }
}
