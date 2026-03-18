<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\RouteStop;
use App\Models\User;

class FirebasePushService
{
    /**
     * Envia notificação para todos usuários vinculados a um ponto
     */
    public static function sendToStop($stopId, $title, $message)
    {
        $stop = RouteStop::with('users')->find($stopId);

        if (!$stop) {
            Log::warning("Stop não encontrado: {$stopId}");
            return;
        }

        foreach ($stop->users as $user) {
            self::sendToUser($user->id, $title, $message);
        }
    }

    /**
     * Envia notificação para um único usuário
     */
    public static function sendToUser($userId, $title, $message)
    {
        $user = User::find($userId);

        if (!$user) {
            Log::warning("Usuário não encontrado: {$userId}");
            return false;
        }

        if (!$user->fcm_token) {
            Log::info("Usuário sem token FCM: {$userId}");
            return false;
        }

        return self::sendPush($user->fcm_token, $title, $message);
    }

    /**
     * Envia para múltiplos usuários (já pronto pra escala)
     */
    public static function sendToMultipleUsers(array $userIds, $title, $message)
    {
        $users = User::whereIn('id', $userIds)->get();

        foreach ($users as $user) {
            if (!$user->fcm_token) continue;

            self::sendPush($user->fcm_token, $title, $message);
        }
    }

    /**
     * Método base de envio para FCM
     */
    private static function sendPush($token, $title, $message)
    {
        try {

            $response = Http::withToken(config('services.firebase.server_key'))
                ->post('https://fcm.googleapis.com/fcm/send', [
                    'to' => $token,
                    'notification' => [
                        'title' => $title,
                        'body' => $message
                    ]
                ]);

            if (!$response->successful()) {
                Log::error("Erro FCM", [
                    'token' => $token,
                    'response' => $response->body()
                ]);
            }

            return $response->successful();
        } catch (\Exception $e) {

            Log::error("Exceção FCM", [
                'message' => $e->getMessage()
            ]);

            return false;
        }
    }
}