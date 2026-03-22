<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Trip;
use App\Models\TripLocation;
use App\Models\RouteStop;
use App\Models\StudentAlertPoint;
use App\Models\TripStopAlert;
use Illuminate\Http\Request;
use App\Helpers\GeoHelper;
use App\Services\FirebasePushService;

class TripLocationController extends Controller
{
    public function store(Request $request, $tripId)
    {
        $request->validate([
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
        ]);

        $trip = Trip::findOrFail($tripId);

        if ($trip->status !== 'in_progress') {
            return response()->json([
                'success' => false,
                'message' => 'Trip not active'
            ], 400);
        }

        $lat = $request->latitude;
        $lng = $request->longitude;

        // ===============================
        // EVITAR SPAM DE LOCALIZAÇÃO
        // ===============================
        $lastLocation = TripLocation::where('trip_id', $trip->id)
            ->latest('recorded_at')
            ->first();

        if ($lastLocation) {

            $seconds = now()->diffInSeconds($lastLocation->recorded_at);

            // if ($seconds < 3) {
            //     return response()->json([
            //         'success' => true,
            //         'message' => 'Location ignored (too soon)'
            //     ]);
            // }

            $distance = GeoHelper::distanceMeters(
                $lat,
                $lng,
                $lastLocation->latitude,
                $lastLocation->longitude
            );

            // if ($distance < 20) {
            //     return response()->json([
            //         'success' => true,
            //         'message' => 'Location ignored (too close)'
            //     ]);
            // }
        }

        // ===============================
        // SALVAR LOCALIZAÇÃO
        // ===============================
        $location = TripLocation::create([
            'school_id' => $trip->school_id,
            'trip_id' => $trip->id,
            'latitude' => $lat,
            'longitude' => $lng,
            'recorded_at' => now(),
        ]);

        // ===============================
        // BUSCAR STOPS
        // ===============================
        $stops = RouteStop::where('school_route_id', $trip->school_route_id)
            ->orderBy('stop_order')
            ->get();

        $approachingEnd = false;

        foreach ($stops as $stop) {

            $distance = GeoHelper::distanceMeters(
                $lat,
                $lng,
                $stop->latitude,
                $stop->longitude
            );

            if ($distance > $stop->radius_meters) {
                continue;
            }

            // ===============================
            // ATUALIZA LAST STOP
            // ===============================
            if (
                !$trip->last_stop_order ||
                $stop->stop_order > $trip->last_stop_order
            ) {
                $trip->update([
                    'last_stop_order' => $stop->stop_order
                ]);
            }

            // ===============================
            // ALERTA DE FIM DE VIAGEM
            // ===============================
            $isLastStop = $stop->stop_order === $stops->last()->stop_order;

            if ($isLastStop && !$trip->end_warning_sent) {
                $trip->update([
                    'end_warning_sent' => true
                ]);

                $approachingEnd = true;
            }

            // ===============================
            // ALERTA PARA ALUNOS (ANTI-SPAM)
            // ===============================
            if (!$stop->allow_student_alert) {
                continue;
            }

            $alerts = StudentAlertPoint::where('route_stop_id', $stop->id)
                ->where('enabled', true)
                ->get();

            foreach ($alerts as $alert) {

                $alreadySent = TripStopAlert::where('trip_id', $trip->id)
                    ->where('student_id', $alert->student_id)
                    ->exists();

                if ($alreadySent) {
                    continue;
                }

                FirebasePushService::sendToUser(
                    $alert->student_id,
                    "🚌 Seu ônibus está chegando",
                    "Parada: {$stop->name}"
                );

                TripStopAlert::create([
                    'trip_id' => $trip->id,
                    'student_id' => $alert->student_id,
                    'route_stop_id' => $stop->id,
                    'sent_at' => now()
                ]);
            }
        }

        return response()->json([
            'success' => true,
            'approaching_end' => $approachingEnd,
            'data' => [
                'lat' => $location->latitude,
                'lng' => $location->longitude,
                'recorded_at' => $location->recorded_at
            ]
        ]);
    }

    public function latest($tripId)
    {
        $trip = Trip::with(['latestLocation'])->findOrFail($tripId);

        if (!$trip->latestLocation) {
            return response()->json([
                'success' => true,
                'data' => null
            ]);
        }

        $lat = $trip->latestLocation->latitude;
        $lng = $trip->latestLocation->longitude;

        // 🔥 BUSCAR STOPS
        $stops = RouteStop::where('school_route_id', $trip->school_route_id)
            ->orderBy('stop_order')
            ->get();

        $nextStop = null;
        $minDistance = null;

        foreach ($stops as $stop) {

            $distance = GeoHelper::distanceMeters(
                $lat,
                $lng,
                $stop->latitude,
                $stop->longitude
            );

            // 🔥 IGNORA STOPS JÁ PASSADOS
            if ($trip->last_stop_order && $stop->stop_order <= $trip->last_stop_order) {
                continue;
            }

            if ($minDistance === null || $distance < $minDistance) {
                $minDistance = $distance;
                $nextStop = $stop;
            }
        }

        return response()->json([
            'success' => true,
            'data' => [
                'lat' => $lat,
                'lng' => $lng,
                'recorded_at' => $trip->latestLocation->recorded_at,

                // 🔥 NOVOS CAMPOS
                'distance' => $minDistance,
                'next_stop' => $nextStop ? [
                    'id' => $nextStop->id,
                    'name' => $nextStop->name,
                    'order' => $nextStop->stop_order,
                ] : null,
            ]
        ]);
    }
}
