<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use App\Models\User;

class FirebasePushService
{
    public static function sendToStop($stopId, $title, $message)
    {
        $users = User::where('route_stop_id', $stopId)->get();

        foreach ($users as $user) {

            if (!$user->fcm_token) continue;

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
