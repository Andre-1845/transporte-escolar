<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\RouteStop;
use App\Models\User;
use App\Models\Trip;

class FirebasePushService
{
    /**
     * SERVICE: FirebasePushService
     *
     * FUNÇÃO: Gerencia envio de notificações push via Firebase Cloud Messaging
     *
     * 🔥 ALTERAÇÕES REALIZADAS:
     * 1. Adicionado método sendToTrip() - envia notificação para todos alunos da viagem
     * 2. Adicionado método sendToMultipleTokens() - envio multicast otimizado
     * 3. Adicionado método sendToDriver() - específico para motorista
     * 4. Adicionado método getTripStudents() - busca alunos da viagem
     * 5. Melhorado sendToMultipleUsers() com batch processing
     * 6. Adicionado tratamento para tokens inválidos (remoção automática)
     * 7. Adicionado suporte a dados personalizados (custom data)
     * 8. Adicionado logs mais detalhados para debug
     */

    /**
     * Envia notificação para todos usuários vinculados a um ponto
     */
    public static function sendToStop($stopId, $title, $message, array $data = [])
    {
        $stop = RouteStop::with('users')->find($stopId);

        if (!$stop) {
            Log::warning("Stop não encontrado: {$stopId}");
            return false;
        }

        $successCount = 0;
        foreach ($stop->users as $user) {
            if (self::sendToUser($user->id, $title, $message, $data)) {
                $successCount++;
            }
        }

        Log::info("Notificações enviadas para stop", [
            'stop_id' => $stopId,
            'total_users' => $stop->users->count(),
            'success_count' => $successCount
        ]);

        return $successCount;
    }

    /**
     * 🔥 NOVO: Envia notificação para todos os alunos de uma viagem
     *
     * @param int $tripId
     * @param string $title
     * @param string $message
     * @param array $data Dados adicionais (ex: stop_id, distance, etc)
     * @return int Quantidade de notificações enviadas
     */
    public static function sendToTrip(int $tripId, string $title, string $message, array $data = [])
    {
        try {
            $trip = Trip::with(['route', 'stopTracking.stop'])->find($tripId);

            if (!$trip) {
                Log::warning("Trip não encontrada para envio de notificação", [
                    'trip_id' => $tripId
                ]);
                return 0;
            }

            // Busca os alunos que têm alerta configurado para os stops desta rota
            $students = self::getTripStudents($trip);

            if ($students->isEmpty()) {
                Log::info("Nenhum aluno encontrado para notificação da trip", [
                    'trip_id' => $tripId
                ]);
                return 0;
            }

            // 🔥 OTIMIZAÇÃO: Extrai tokens válidos
            $tokens = $students->pluck('fcm_token')->filter()->toArray();

            if (empty($tokens)) {
                Log::info("Nenhum token FCM válido encontrado para alunos da trip", [
                    'trip_id' => $tripId,
                    'students_count' => $students->count()
                ]);
                return 0;
            }

            // 🔥 NOVO: Envia em lote para múltiplos tokens (mais eficiente)
            $result = self::sendToMultipleTokens($tokens, $title, $message, $data);

            Log::info("Notificações enviadas para trip", [
                'trip_id' => $tripId,
                'students_count' => $students->count(),
                'tokens_sent' => count($tokens),
                'success' => $result
            ]);

            return $result ? count($tokens) : 0;
        } catch (\Exception $e) {
            Log::error("Erro ao enviar notificação para trip", [
                'trip_id' => $tripId,
                'error' => $e->getMessage()
            ]);
            return 0;
        }
    }

    /**
     * 🔥 NOVO: Envia notificação para o motorista da viagem
     *
     * @param int $tripId
     * @param string $title
     * @param string $message
     * @param array $data Dados adicionais
     * @return bool
     */
    public static function sendToDriver(int $tripId, string $title, string $message, array $data = [])
    {
        try {
            $trip = Trip::find($tripId);

            if (!$trip || !$trip->driver_id) {
                Log::warning("Motorista não encontrado para envio de notificação", [
                    'trip_id' => $tripId
                ]);
                return false;
            }

            return self::sendToUser($trip->driver_id, $title, $message, $data);
        } catch (\Exception $e) {
            Log::error("Erro ao enviar notificação para motorista", [
                'trip_id' => $tripId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * 🔥 NOVO: Envia para múltiplos tokens em uma única requisição (multicast)
     *
     * @param array $tokens Lista de tokens FCM (máximo 1000 por requisição)
     * @param string $title
     * @param string $message
     * @param array $data Dados adicionais
     * @return bool
     */
    public static function sendToMultipleTokens(array $tokens, string $title, string $message, array $data = []): bool
    {
        if (empty($tokens)) {
            return false;
        }

        try {
            // 🔥 FCM suporta até 1000 tokens por requisição multicast
            $chunks = array_chunk($tokens, 1000);
            $allSuccess = true;

            foreach ($chunks as $chunk) {
                $payload = [
                    'registration_ids' => $chunk,
                    'notification' => [
                        'title' => $title,
                        'body' => $message,
                        'sound' => 'default',
                        'badge' => 1
                    ]
                ];

                // 🔥 Adiciona dados personalizados se fornecidos
                if (!empty($data)) {
                    $payload['data'] = $data;
                }

                $response = Http::withToken(config('services.firebase.server_key'))
                    ->post('https://fcm.googleapis.com/fcm/send', $payload);

                if (!$response->successful()) {
                    $allSuccess = false;
                    Log::error("Erro FCM multicast", [
                        'tokens_count' => count($chunk),
                        'response' => $response->body()
                    ]);

                    // 🔥 NOVO: Verifica tokens inválidos na resposta
                    $responseData = $response->json();
                    if (isset($responseData['results'])) {
                        self::handleInvalidTokens($chunk, $responseData['results']);
                    }
                }
            }

            return $allSuccess;
        } catch (\Exception $e) {
            Log::error("Exceção FCM multicast", [
                'tokens_count' => count($tokens),
                'message' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Envia notificação para um único usuário
     *
     * 🔥 ALTERADO: Adicionado suporte a dados personalizados
     */
    public static function sendToUser($userId, $title, $message, array $data = [])
    {
        $user = User::find($userId);

        if (!$user) {
            Log::warning("Usuário não encontrado: {$userId}");
            return false;
        }

        if (!$user->fcm_token) {
            Log::info("Usuário sem token FCM: {$userId}", [
                'user_name' => $user->name
            ]);
            return false;
        }

        return self::sendPush($user->fcm_token, $title, $message, $data);
    }

    /**
     * Envia para múltiplos usuários (já pronto pra escala)
     *
     * 🔥 ALTERADO: Agora usa sendToMultipleTokens para melhor performance
     */
    public static function sendToMultipleUsers(array $userIds, $title, $message, array $data = [])
    {
        if (empty($userIds)) {
            return 0;
        }

        $users = User::whereIn('id', $userIds)->get();
        $tokens = $users->pluck('fcm_token')->filter()->toArray();

        if (empty($tokens)) {
            return 0;
        }

        $success = self::sendToMultipleTokens($tokens, $title, $message, $data);

        return $success ? count($tokens) : 0;
    }

    /**
     * 🔥 NOVO: Busca todos os alunos de uma viagem
     *
     * @param Trip $trip
     * @return \Illuminate\Support\Collection
     */
    private static function getTripStudents(Trip $trip)
    {
        // Busca todos os stop IDs da rota
        $stopIds = $trip->stopTracking()->pluck('stop_id')->toArray();

        if (empty($stopIds)) {
            return collect([]);
        }

        // Busca alunos que têm alerta configurado para algum stop da rota
        $students = User::whereHas('roles', function ($q) {
            $q->where('name', 'student');
        })
            ->where('school_id', $trip->school_id)
            ->whereHas('studentAlertPoints', function ($q) use ($stopIds) {
                $q->whereIn('route_stop_id', $stopIds)
                    ->where('enabled', true);
            })
            ->with('studentAlertPoints')
            ->get();

        return $students;
    }

    /**
     * 🔥 NOVO: Remove tokens inválidos do banco de dados
     *
     * @param array $tokens Tokens enviados
     * @param array $results Respostas do FCM
     */
    private static function handleInvalidTokens(array $tokens, array $results)
    {
        $invalidTokens = [];

        foreach ($results as $index => $result) {
            // Verifica se o token é inválido (NotRegistered)
            if (
                isset($result['error']) &&
                ($result['error'] === 'NotRegistered' || $result['error'] === 'InvalidRegistration')
            ) {
                $invalidTokens[] = $tokens[$index];
            }
        }

        if (!empty($invalidTokens)) {
            // Remove tokens inválidos do banco
            User::whereIn('fcm_token', $invalidTokens)->update(['fcm_token' => null]);

            Log::info("Tokens FCM inválidos removidos", [
                'count' => count($invalidTokens)
            ]);
        }
    }

    /**
     * Método base de envio para FCM
     *
     * 🔥 ALTERADO: Adicionado suporte a dados personalizados e verificação de token inválido
     */
    private static function sendPush($token, $title, $message, array $data = [])
    {
        try {
            $payload = [
                'to' => $token,
                'notification' => [
                    'title' => $title,
                    'body' => $message,
                    'sound' => 'default',
                    'badge' => 1
                ]
            ];

            // 🔥 Adiciona dados personalizados se fornecidos
            if (!empty($data)) {
                $payload['data'] = $data;
            }

            $response = Http::withToken(config('services.firebase.server_key'))
                ->post('https://fcm.googleapis.com/fcm/send', $payload);

            if (!$response->successful()) {
                $responseData = $response->json();

                // 🔥 Verifica se o token é inválido
                if (isset($responseData['results'][0]['error'])) {
                    $error = $responseData['results'][0]['error'];
                    if ($error === 'NotRegistered' || $error === 'InvalidRegistration') {
                        // Remove token inválido
                        User::where('fcm_token', $token)->update(['fcm_token' => null]);
                        Log::info("Token FCM removido por ser inválido", ['token' => substr($token, 0, 20) . '...']);
                    }
                }

                Log::error("Erro FCM", [
                    'token' => substr($token, 0, 20) . '...',
                    'response' => $response->body()
                ]);

                return false;
            }

            return true;
        } catch (\Exception $e) {
            Log::error("Exceção FCM", [
                'message' => $e->getMessage(),
                'token' => substr($token, 0, 20) . '...'
            ]);
            return false;
        }
    }
}
