<?php

namespace App\Services;

use App\Models\Trip;
use App\Models\TripStopTracking;
use App\Models\RouteStop;
use App\Helpers\GeoHelper;
use App\Models\TripLocation;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use App\Services\AlertService;
use Illuminate\Support\Facades\Log;

class StopPointManager
{
    /**
     * SERVICE: StopPointManager
     *
     * FUNÇÃO: Gerencia toda a lógica de processamento dos stop points
     */

    private $alertService;
    private $movementAnalyzer;

    // Raio de aproximação é 1.5x o raio do stop point
    const APPROACH_RATIO = 1.5;
    const BEARING_TOLERANCE = 45; // graus de tolerância para considerar "na direção"

    public function __construct(AlertService $alertService, MovementAnalyzer $movementAnalyzer)
    {
        $this->alertService = $alertService;
        $this->movementAnalyzer = $movementAnalyzer;
    }

    /**
     * Processa a localização atual com análise de direção
     */
    public function processLocation(Trip $trip, float $lat, float $lng, ?TripLocation $previousLocation = null): array
    {
        return DB::transaction(function () use ($trip, $lat, $lng, $previousLocation) {

            $trip->load('stopTracking.stop');

            $result = [
                'current_stop' => null,
                'distance' => null,
                'alert_triggered' => false,
                'advanced' => false,
                'eta_seconds' => null,
                'bearing' => null,
                'speed' => null,
                'movement_status' => 'unknown'
            ];

            // Busca o stop point atual
            $currentTracking = $trip->getCurrentStop();

            if (!$currentTracking) {
                $this->checkTripCompletion($trip);
                return $result;
            }

            $currentStop = $currentTracking->stop;
            $distance = GeoHelper::distanceMeters(
                $lat,
                $lng,
                $currentStop->latitude,
                $currentStop->longitude
            );

            $result['current_stop'] = $currentStop->stop_order;
            $result['distance'] = $distance;

            // ===============================
            // ANALISA DIREÇÃO E MOVIMENTO
            // ===============================

            $bearing = null;
            $speed = 0;
            $movementStatus = 'moving';
            $isApproachingByDirection = false;
            $lastDistance = $distance; // fallback para distância atual

            if ($previousLocation) {
                $movement = $this->movementAnalyzer->analyzeMovement(
                    $previousLocation->latitude,
                    $previousLocation->longitude,
                    $previousLocation->recorded_at,
                    $lat,
                    $lng,
                    now()
                );

                $bearing = $movement['bearing'];
                $speed = $movement['speed'];

                // Calcula ângulo do ônibus para o stop
                $angleToStop = $this->movementAnalyzer->calculateBearing(
                    $lat,
                    $lng,
                    $currentStop->latitude,
                    $currentStop->longitude
                );

                // Verifica se está na direção do stop
                $angleDiff = abs($bearing - $angleToStop);
                $angleDiff = min($angleDiff, 360 - $angleDiff);
                $isApproachingByDirection = $angleDiff <= self::BEARING_TOLERANCE;

                // 🔥 CORREÇÃO: Calcula distância anterior com fallback
                $lastDistance = $previousLocation->getDistanceToStop($currentStop->id) ?? $distance;

                // 🔥 CORREÇÃO: Remove parâmetro duplicado
                $movementStatus = $this->movementAnalyzer->getMovementStatus(
                    $speed,
                    $distance,
                    $lastDistance,
                    $bearing,
                    $angleToStop
                );

                $result['bearing'] = $bearing;
                $result['speed'] = $speed;
                $result['movement_status'] = $movementStatus;
            }

            // Atualiza distância mínima
            $currentTracking->updateMinDistance($distance);

            // ===============================
            // LÓGICA COM DIREÇÃO (MAIS PRECISA)
            // ===============================

            $radius = $currentStop->radius_meters ?? 200;
            $approachRadius = $radius * self::APPROACH_RATIO;

            // ESTADO 1: pending → approaching
            // Só considera aproximação se estiver na direção correta ou muito próximo
            $shouldApproach = $distance <= $approachRadius &&
                ($isApproachingByDirection || $distance <= $radius);

            if ($currentTracking->status === 'pending' && $shouldApproach) {
                $currentTracking->markAsApproaching($distance, now());
                $result['alert_triggered'] = true;

                $this->alertService->sendApproachingAlert($trip, $currentStop, $distance, $bearing);
            }

            // ESTADO 2: approaching → reached
            // Confirma chegada baseado em direção E distância
            $hasArrived = $distance <= $radius;

            // Se está muito próximo, chegou independente da direção
            if ($distance <= 20) {
                $hasArrived = true;
            }

            if ($currentTracking->status === 'approaching' && $hasArrived) {
                $currentTracking->markAsReached(now());
                $result['alert_triggered'] = true;

                $this->alertService->sendReachedAlert($trip, $currentStop);

                // Avança para próximo stop
                $nextTracking = $this->advanceToNextStop($trip, $currentTracking);
                $result['advanced'] = true;

                // 🔥 FORÇA RECARREGAMENTO DO ESTADO
                $trip->load('stopTracking.stop');

                if ($nextTracking) {
                    $this->handleNextStop($trip, $nextTracking, $lat, $lng, $result);
                }
            }

            // ESTADO 3: approaching → passed (passou sem parar)
            // Detecta quando o ônibus passa pelo ponto sem marcar reached
            if ($currentTracking->status === 'approaching' && !$hasArrived && $movementStatus === 'leaving') {
                // Verifica se já passou do ponto (distância aumentando significativamente)
                $minDistance = $currentTracking->min_distance ?? $distance;
                $passedThreshold = $distance > ($minDistance + 50); // 50m além do mínimo

                if ($passedThreshold) {
                    $currentTracking->markAsPassed(now());

                    Log::info('🚌 Ônibus passou pelo stop point sem parar', [
                        'trip_id' => $trip->id,
                        'stop_order' => $currentTracking->stop_order,
                        'min_distance' => $minDistance,
                        'current_distance' => $distance,
                        'bearing' => $bearing
                    ]);

                    // Avança para próximo stop mesmo sem ter parado
                    $nextTracking = $this->advanceToNextStop($trip, $currentTracking);
                    $result['advanced'] = true;

                    // 🔥 FORÇA RECARREGAMENTO DO ESTADO
                    $trip->load('stopTracking.stop');

                    if ($nextTracking) {
                        $this->handleNextStop($trip, $nextTracking, $lat, $lng, $result);
                    }
                }
            }

            // ESTADO 4: reached → passed (após parar e sair)
            if ($currentTracking->status === 'reached' && $movementStatus === 'leaving') {
                $currentTracking->markAsPassed(now());
            }

            // ===============================
            // PREDIÇÃO DE PRÓXIMO STOP (OTIMIZAÇÃO)
            // ===============================

            if ($bearing !== null && $speed > 5) { // Só prediz se estiver se movendo
                $predictedNext = $this->movementAnalyzer->predictNextStop(
                    $lat,
                    $lng,
                    $bearing,
                    $trip->stopTracking()->with('stop')->get(),
                    $currentTracking->stop_order
                );

                if ($predictedNext) {
                    $result['predicted_next_stop'] = [
                        'order' => $predictedNext->stop_order,
                        'name' => $predictedNext->stop->name,
                        'confidence' => $this->calculatePredictionConfidence($bearing, $lat, $lng, $predictedNext)
                    ];
                }
            }

            // ===============================
            // CÁLCULO DO ETA (AGORA COM VELOCIDADE)
            // ===============================

            $finalTracking = $trip->getCurrentStop();
            if ($finalTracking) {
                $finalDistance = GeoHelper::distanceMeters(
                    $lat,
                    $lng,
                    $finalTracking->stop->latitude,
                    $finalTracking->stop->longitude
                );

                // Usa velocidade atual se disponível, senão usa média histórica
                $result['eta_seconds'] = $finalTracking->calculateETAFromCurrentPosition(
                    $lat,
                    $lng,
                    $finalDistance,
                    $speed
                );
            } else {
                $result['eta_seconds'] = 0;
            }
            Log::info('🔥 PROCESS LOCATION EXECUTADO', [
                'trip_id' => $trip->id,
                'lat' => $lat,
                'lng' => $lng
            ]);
            return $result;
        });
    }

    /**
     * Processa o próximo stop após avanço
     */
    private function handleNextStop(Trip $trip, $nextTracking, float $lat, float $lng, array &$result)
    {
        $nextStop = $nextTracking->stop;
        $nextDistance = GeoHelper::distanceMeters(
            $lat,
            $lng,
            $nextStop->latitude,
            $nextStop->longitude
        );

        $result['current_stop'] = $nextStop->stop_order;
        $result['distance'] = $nextDistance;

        $nextRadius = $nextStop->radius_meters ?? 200;
        $nextApproachRadius = $nextRadius * self::APPROACH_RATIO;

        // Se já estiver próximo do próximo stop, já marca como aproximando
        if ($nextDistance <= $nextApproachRadius) {
            $nextTracking->markAsApproaching($nextDistance, now());
            $result['alert_triggered'] = true;
            $this->alertService->sendApproachingAlert($trip, $nextStop, $nextDistance, null);
        }
    }

    /**
     * Calcula confiança da predição baseado no alinhamento
     */
    private function calculatePredictionConfidence(float $bearing, float $lat, float $lng, $predictedStop): float
    {
        $angleToStop = $this->movementAnalyzer->calculateBearing(
            $lat,
            $lng,
            $predictedStop->stop->latitude,
            $predictedStop->stop->longitude
        );

        $angleDiff = abs($bearing - $angleToStop);
        $angleDiff = min($angleDiff, 360 - $angleDiff);

        // Confiança baseada no alinhamento (0-1)
        return 1 - ($angleDiff / 180);
    }

    /**
     * Avança para o próximo stop point
     */
    private function advanceToNextStop(Trip $trip, TripStopTracking $currentTracking)
    {
        $nextTracking = $trip->stopTracking()
            ->where('stop_order', '>', $currentTracking->stop_order)
            ->where('status', 'pending')
            ->orderBy('stop_order')
            ->first();

        if ($nextTracking) {
            Log::info('➡️ Avançando para próximo stop', [
                'trip_id' => $trip->id,
                'from_stop' => $currentTracking->stop_order,
                'to_stop' => $nextTracking->stop_order
            ]);
        }

        return $nextTracking;
    }

    /**
     * Verifica se a viagem foi concluída
     */
    private function checkTripCompletion(Trip $trip)
    {
        $pendingStops = $trip->stopTracking()
            ->whereIn('status', ['pending', 'approaching'])
            ->count();

        if ($pendingStops === 0) {
            $trip->update([
                'status' => 'finished',
                'auto_finish_pending' => false,
                'auto_finish_at' => null
            ]);

            Log::info('🏁 Viagem finalizada', ['trip_id' => $trip->id]);
        }
    }

    /**
     * Calcula ETA total da viagem até o último stop
     */
    public function calculateTotalETA(Trip $trip, float $lat, float $lng): int
    {
        $currentTracking = $trip->getCurrentStop();
        if (!$currentTracking) {
            return 0;
        }

        return $currentTracking->calculateETAFromCurrentPosition($lat, $lng);
    }
}
