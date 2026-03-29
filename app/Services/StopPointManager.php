<?php

namespace App\Services;

use App\Models\Trip;
use App\Models\TripStopTracking;
use App\Helpers\GeoHelper;
use App\Models\StopTimeMetrics;
use App\Models\TripLocation;
use Illuminate\Support\Facades\DB;
use App\Services\AlertService;
use Illuminate\Support\Facades\Log;

class StopPointManager
{
    private $alertService;
    private $movementAnalyzer;

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
                'heading' => null,
                'speed' => null,
                'movement_status' => 'moving'
            ];

            // =========================
            // STOP ATUAL
            // =========================
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
            // MOVEMENT (🔥 CORRIGIDO)
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
                $bearing = $movement['bearing'];

                $lastDistance = $previousLocation->getDistanceToStop($currentStop->id);

                // 🔥 direção até o stop
                $stopBearing = $this->movementAnalyzer->calculateBearing(
                    $lat,
                    $lng,
                    $currentStop->latitude,
                    $currentStop->longitude
                );

                $movementStatus = $this->movementAnalyzer->getMovementStatus(
                    $speed,
                    $distance,
                    $lastDistance,
                    $bearing,
                    $stopBearing
                );

                $result['speed'] = $speed;
                $result['bearing'] = $bearing;
                $result['heading'] = $movement['heading'];
                $result['movement_status'] = $movementStatus;
            }

            // =========================
            // RAIO
            // =========================
            $radius = 80; // 🔥 chegou
            $approachRadius = 200; // 🔥 chegando

            // =========================
            // ESTADOS
            // =========================

            // approaching
            if (
                $currentTracking->status === 'pending' &&
                $distance <= $approachRadius &&
                $distance > $radius
            ) {
                $currentTracking->markAsApproaching($distance);

                Log::info('➡️ APPROACH', [
                    'stop' => $currentTracking->stop_order,
                    'distance' => $distance
                ]);

                return $result;
            }

            // reached
            if (
                $currentTracking->status === 'approaching' &&
                $distance <= $radius
            ) {
                $currentTracking->markAsReached();

                Log::info('✅ REACHED', [
                    'stop' => $currentTracking->stop_order,
                    'distance' => $distance
                ]);

                return $result;
            }

            // passed
            if (
                $currentTracking->status === 'reached' &&
                $distance > ($radius + 50)
            ) {

                // 🔥 MÉTRICAS
                $previousStop = TripStopTracking::where('trip_id', $trip->id)
                    ->where('stop_order', $currentTracking->stop_order - 1)
                    ->first();

                if ($previousStop && $previousStop->reached_at && $currentTracking->reached_at) {

                    $timeSeconds = strtotime($currentTracking->reached_at) - strtotime($previousStop->reached_at);

                    $metric = StopTimeMetrics::firstOrNew([
                        'route_id' => $trip->school_route_id,
                        'from_stop_id' => $previousStop->stop_id,
                        'to_stop_id' => $currentTracking->stop_id,
                    ]);

                    if ($metric->exists) {
                        $metric->avg_time_seconds = intval(
                            ($metric->avg_time_seconds * $metric->sample_count + $timeSeconds)
                                / ($metric->sample_count + 1)
                        );
                        $metric->sample_count += 1;
                    } else {
                        $metric->avg_time_seconds = $timeSeconds;
                        $metric->sample_count = 1;
                    }

                    $metric->save();
                }

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
