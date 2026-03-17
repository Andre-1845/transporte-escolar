<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use App\Models\RouteStop;

class FirebasePushService
{
    public static function sendToStop($stopId, $title, $message)
    {
        $stop = RouteStop::with('users')->find($stopId);

        if (!$stop) {
            return;
        }

        foreach ($stop->users as $user) {

            if (!$user->fcm_token) {
                continue;
            }

            Http::withToken(config('services.firebase.server_key'))
                ->post('https://fcm.googleapis.com/fcm/send', [
                    'to' => $user->fcm_token,
                    'notification' => [
                        'title' => $title,
                        'body' => $message
                    ]
                ]);
        }
    }
}