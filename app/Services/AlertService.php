<?php

namespace App\Services;

use App\Models\Trip;
use App\Models\RouteStop;
use App\Models\StudentAlertPoint;
use App\Models\TripStopAlert;
use App\Models\TripAlertsLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AlertService
{
    /**
     * SERVICE: AlertService
     *
     * FUNÇÃO: Gerencia todos os alertas do sistema (push, logs, etc)
     *
     * 🔥 ALTERAÇÕES:
     * - Agora usa o FirebasePushService atualizado com sendToTrip() e sendToDriver()
     * - Removido código redundante que buscava alunos
     * - Simplificado com os novos métodos do Firebase
     */

    /**
     * Envia alerta de aproximação
     */
    public function sendApproachingAlert(Trip $trip, RouteStop $stop, float $distance, ?float $bearing = null)
    {
        // Alerta para motorista (in-app via banco)
        $this->sendDriverAlert($trip, $stop, 'approaching', $distance);

        // 🔥 Envia push para motorista via Firebase
        $distanceText = $distance < 100
            ? round($distance) . 'm'
            : number_format($distance / 1000, 1) . 'km';

        $directionText = $bearing !== null ? $this->getDirectionText($bearing) : '';

        FirebasePushService::sendToDriver(
            $trip->id,
            "🚌 Aproximando da parada",
            "{$stop->name} - Distância: {$distanceText}{$directionText}",
            [
                'type' => 'approaching',
                'stop_id' => $stop->id,
                'stop_name' => $stop->name,
                'distance' => $distance,
                'bearing' => $bearing
            ]
        );

        // 🔥 Envia para todos os alunos vinculados a este stop point
        // O FirebasePushService já tem o método sendToStop que faz isso
        FirebasePushService::sendToStop(
            $stop->id,
            "🚌 Seu ônibus está chegando!",
            "Parada: {$stop->name} - Distância: {$distanceText}{$directionText}",
            [
                'type' => 'approaching',
                'stop_id' => $stop->id,
                'stop_name' => $stop->name,
                'distance' => $distance
            ]
        );

        // Log de auditoria para alunos (já feito pelo sendToStop, mas mantemos para consistência)
        $studentAlerts = StudentAlertPoint::where('route_stop_id', $stop->id)
            ->where('enabled', true)
            ->get();

        foreach ($studentAlerts as $alert) {
            // Evita alertas duplicados no log
            $alreadyLogged = TripStopAlert::where('trip_id', $trip->id)
                ->where('student_id', $alert->student_id)
                ->exists();

            if (!$alreadyLogged) {
                TripStopAlert::create([
                    'trip_id' => $trip->id,
                    'student_id' => $alert->student_id,
                    'route_stop_id' => $stop->id,
                    'sent_at' => now()
                ]);

                $this->logAlert($trip, $stop, $alert->student_id, 'approaching', $distance);
            }
        }
    }

    /**
     * Envia alerta de chegada
     */
    public function sendReachedAlert(Trip $trip, RouteStop $stop)
    {
        // Alerta para motorista (in-app)
        $this->sendDriverAlert($trip, $stop, 'reached', 0);

        // 🔥 Envia push para motorista
        FirebasePushService::sendToDriver(
            $trip->id,
            "🚌 Chegou na parada!",
            "{$stop->name} - Embarque/desembarque",
            [
                'type' => 'reached',
                'stop_id' => $stop->id,
                'stop_name' => $stop->name
            ]
        );

        // 🔥 Envia para todos os alunos vinculados a este stop point
        FirebasePushService::sendToStop(
            $stop->id,
            "🚌 Ônibus chegou!",
            "Parada: {$stop->name} - Embarque/desembarque",
            [
                'type' => 'reached',
                'stop_id' => $stop->id,
                'stop_name' => $stop->name
            ]
        );

        // Log de auditoria para alunos
        $studentAlerts = StudentAlertPoint::where('route_stop_id', $stop->id)
            ->where('enabled', true)
            ->get();

        foreach ($studentAlerts as $alert) {
            $this->logAlert($trip, $stop, $alert->student_id, 'reached', 0);
        }
    }

    /**
     * Envia alerta de aproximação do fim da rota
     */
    public function sendEndWarning(Trip $trip, ?int $etaSeconds = null)
    {
        $etaText = '';
        if ($etaSeconds !== null) {
            $minutes = round($etaSeconds / 60);
            $etaText = $minutes > 0 ? " - Chegada em {$minutes} min" : " - Chegando agora";
        }

        // Alerta para motorista (in-app)
        DB::table('driver_alerts')->insert([
            'trip_id' => $trip->id,
            'driver_id' => $trip->driver_id,
            'alert_type' => 'end_warning',
            'message' => "Fim da rota se aproximando{$etaText}",
            'read' => false,
            'created_at' => now()
        ]);

        // 🔥 Envia push para motorista
        FirebasePushService::sendToDriver(
            $trip->id,
            "🏁 Fim da rota",
            "O ônibus está se aproximando do destino final{$etaText}",
            [
                'type' => 'end_warning',
                'eta_seconds' => $etaSeconds
            ]
        );

        // 🔥 Envia broadcast para todos os alunos da viagem
        FirebasePushService::sendToTrip(
            $trip->id,
            "🏁 Fim da rota",
            "O ônibus está se aproximando do destino final{$etaText}",
            [
                'type' => 'end_warning',
                'eta_seconds' => $etaSeconds
            ]
        );

        // Log de auditoria
        $this->logAlert($trip, null, null, 'end_warning', 0);

        Log::info('📢 Alerta de fim de rota enviado', [
            'trip_id' => $trip->id,
            'eta' => $etaSeconds
        ]);
    }

    /**
     * Envia alerta personalizado para todos os alunos da viagem
     */
    public function sendBroadcastToTrip(Trip $trip, string $title, string $body, string $type = 'broadcast', array $data = [])
    {
        // 🔥 Usa o método sendToTrip do Firebase
        $sent = FirebasePushService::sendToTrip($trip->id, $title, $body, array_merge($data, ['type' => $type]));

        // Log de auditoria
        TripAlertsLog::create([
            'trip_id' => $trip->id,
            'stop_id' => null,
            'user_id' => null,
            'alert_type' => $type,
            'distance_at_alert' => 0,
            'sent_at' => now()
        ]);

        Log::info('📢 Broadcast enviado para trip', [
            'trip_id' => $trip->id,
            'title' => $title,
            'notifications_sent' => $sent
        ]);

        return $sent;
    }

    /**
     * Envia alerta para o motorista (salva no banco para o app consultar)
     */
    private function sendDriverAlert(Trip $trip, RouteStop $stop, string $type, float $distance)
    {
        $message = $type === 'approaching'
            ? "Aproximando da parada: {$stop->name} - Distância: " . round($distance) . "m"
            : "Chegou na parada: {$stop->name}";

        DB::table('driver_alerts')->insert([
            'trip_id' => $trip->id,
            'driver_id' => $trip->driver_id,
            'stop_id' => $stop->id,
            'alert_type' => $type,
            'distance' => $distance,
            'message' => $message,
            'read' => false,
            'created_at' => now()
        ]);
    }

    /**
     * Registra alerta no log de auditoria
     */
    private function logAlert(Trip $trip, ?RouteStop $stop, ?int $userId, string $type, float $distance)
    {
        TripAlertsLog::create([
            'trip_id' => $trip->id,
            'stop_id' => $stop?->id,
            'user_id' => $userId ?? $trip->driver_id,
            'alert_type' => $type,
            'distance_at_alert' => $distance,
            'sent_at' => now()
        ]);
    }

    /**
     * Retorna texto descritivo da direção
     */
    private function getDirectionText(float $bearing): string
    {
        $directions = [
            'N' => [0, 22.5],
            'NE' => [22.5, 67.5],
            'E' => [67.5, 112.5],
            'SE' => [112.5, 157.5],
            'S' => [157.5, 202.5],
            'SW' => [202.5, 247.5],
            'W' => [247.5, 292.5],
            'NW' => [292.5, 337.5],
            'N' => [337.5, 360]
        ];

        foreach ($directions as $direction => $range) {
            if ($bearing >= $range[0] && $bearing < $range[1]) {
                return " - vindo do {$direction}";
            }
        }

        return '';
    }
}
