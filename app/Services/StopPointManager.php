<?php

namespace App\Services;

use App\Models\Trip;
use App\Models\TripStopTracking;
use App\Helpers\GeoHelper;
use App\Models\TripLocation;
use Illuminate\Support\Facades\DB;
use App\Services\AlertService;
use Illuminate\Support\Facades\Log;

class StopPointManager
{
    private $alertService;
    private $movementAnalyzer;

    const APPROACH_RATIO = 1.5;

    public function __construct(AlertService $alertService, MovementAnalyzer $movementAnalyzer)
    {
        $this->alertService = $alertService;
        $this->movementAnalyzer = $movementAnalyzer;
    }

    public function processLocation(Trip $trip, float $lat, float $lng, ?TripLocation $previousLocation = null): array
    {
        return DB::transaction(function () use ($trip, $lat, $lng, $previousLocation) {

            $result = [
                'current_stop' => null,
                'distance' => null,
                'advanced' => false,
                'eta_seconds' => null,
                'bearing' => null,
                'speed' => null,
                'movement_status' => 'moving'
            ];

            // 🔥 CORREÇÃO PRINCIPAL: query direta
            $currentTracking = TripStopTracking::where('trip_id', $trip->id)
                ->whereIn('status', ['pending', 'approaching'])
                ->orderBy('stop_order')
                ->first();

            if (!$currentTracking) {
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

            // =========================
            // MOVEMENT
            // =========================
            $speed = 0;
            $movementStatus = 'moving';

            if ($previousLocation) {
                $movement = $this->movementAnalyzer->analyzeMovement(
                    $previousLocation->latitude,
                    $previousLocation->longitude,
                    $previousLocation->recorded_at,
                    $lat,
                    $lng,
                    now()
                );

                $speed = $movement['speed'];

                $lastDistance = $previousLocation->getDistanceToStop($currentStop->id);

                $movementStatus = $this->movementAnalyzer->getMovementStatus(
                    $speed,
                    $distance,
                    $lastDistance
                );

                $result['speed'] = $speed;
                $result['movement_status'] = $movementStatus;
                $result['bearing'] = $movement['bearing'] ?? null;
                $result['speed'] = $movement['speed'] ?? null;
            }

            $radius = $currentStop->radius_meters ?? 200;
            $approachRadius = $radius * self::APPROACH_RATIO;

            // =========================
            // ESTADOS (CORRIGIDO)
            // =========================

            // 1. pending → approaching
            if ($currentTracking->status === 'pending' && $distance <= $approachRadius) {

                $currentTracking->markAsApproaching($distance);

                Log::info('➡️ APPROACH', [
                    'stop' => $currentTracking->stop_order,
                    'distance' => $distance
                ]);

                // 🔥 PARA AQUI (IMPORTANTE)
                return $result;
            }


            // 2. approaching → reached
            if ($currentTracking->status === 'approaching' && $distance <= $radius) {

                $currentTracking->markAsReached();

                Log::info('✅ REACHED', [
                    'stop' => $currentTracking->stop_order
                ]);

                // 🔥 PARA AQUI
                return $result;
            }


            // 3. reached → passed (SÓ quando sair do raio)
            if (
                $currentTracking->status === 'reached' &&
                $distance > ($radius + 30) // buffer de saída
            ) {

                $currentTracking->markAsPassed();

                $result['advanced'] = true;

                Log::info('➡️ PASSED → NEXT', [
                    'stop' => $currentTracking->stop_order
                ]);

                return $result;
            }

            // =========================
            // ETA
            // =========================
            $currentTracking = TripStopTracking::where('trip_id', $trip->id)
                ->whereIn('status', ['pending', 'approaching'])
                ->orderBy('stop_order')
                ->first();

            if ($currentTracking) {
                $result['eta_seconds'] = $currentTracking->calculateETAFromCurrentPosition(
                    $lat,
                    $lng,
                    $distance,
                    $speed
                );
            }

            return $result;
        });
    }
}
