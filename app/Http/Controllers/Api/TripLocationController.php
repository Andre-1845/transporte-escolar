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
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

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
        // SALVAR LOCALIZAÇÃO
        // ===============================
        $location = TripLocation::create([
            'school_id' => $trip->school_id,
            'trip_id' => $trip->id,
            'latitude' => $lat,
            'longitude' => $lng,
            'recorded_at' => now(),
        ]);

        $stops = RouteStop::where('school_route_id', $trip->school_route_id)
            ->orderBy('stop_order')
            ->get()
            ->values();

        $shouldUpdate = false;
        $data = [];
        $approachingEnd = false;

        // 🔥 PARADA ESPERADA
        $currentStop = $stops->firstWhere(
            'stop_order',
            $trip->current_stop_order
        );

        if ($currentStop) {

            $distance = GeoHelper::distanceMeters(
                $lat,
                $lng,
                $currentStop->latitude,
                $currentStop->longitude
            );

            // ===============================
            // CHEGOU NA PARADA
            // ===============================
            if ($distance <= $currentStop->radius_meters && !$trip->arrived_at_stop) {

                $now = now();

                // 🔥 MÉTRICAS
                if (
                    $trip->last_stop_at &&
                    $trip->last_stop_id &&
                    $trip->last_stop_id != $currentStop->id
                ) {
                    $timeSpent = $now->diffInSeconds($trip->last_stop_at);

                    $this->updateStopMetrics(
                        $trip,
                        $trip->last_stop_id,
                        $currentStop->id,
                        $timeSpent
                    );
                }

                $data['arrived_at_stop'] = true;
                $data['last_stop_at'] = $now;
                $data['last_stop_id'] = $currentStop->id;

                $shouldUpdate = true;
            }

            // ===============================
            // SAIU DA PARADA → AVANÇA
            // ===============================
            if ($trip->arrived_at_stop && $distance > ($currentStop->radius_meters + 50)) {

                $data['current_stop_order'] = $trip->current_stop_order + 1;
                $data['arrived_at_stop'] = false;

                $shouldUpdate = true;
            }

            // ===============================
            // ÚLTIMA PARADA
            // ===============================
            $isLastStop = $currentStop->stop_order === $stops->last()->stop_order;

            if ($isLastStop) {

                if (!$trip->auto_finish_pending) {
                    $data['auto_finish_pending'] = true;
                    $data['auto_finish_at'] = now()->addSeconds(15);
                    $shouldUpdate = true;
                }

                if (!$trip->end_warning_sent) {
                    $data['end_warning_sent'] = true;
                    $approachingEnd = true;
                    $shouldUpdate = true;
                }
            }

            // ===============================
            // ALERTAS DE ALUNOS
            // ===============================
            if ($currentStop->allow_student_alert) {

                $alerts = StudentAlertPoint::where('route_stop_id', $currentStop->id)
                    ->where('enabled', true)
                    ->get();

                foreach ($alerts as $alert) {

                    $alreadySent = TripStopAlert::where('trip_id', $trip->id)
                        ->where('student_id', $alert->student_id)
                        ->exists();

                    if ($alreadySent) continue;

                    FirebasePushService::sendToUser(
                        $alert->student_id,
                        "🚌 Seu ônibus está chegando",
                        "Parada: {$currentStop->name}"
                    );

                    TripStopAlert::create([
                        'trip_id' => $trip->id,
                        'student_id' => $alert->student_id,
                        'route_stop_id' => $currentStop->id,
                        'sent_at' => now()
                    ]);
                }
            }
        }

        if ($shouldUpdate) {
            $trip->update($data);
        }

        // ===============================
        // LIMPEZA
        // ===============================
        if (rand(1, 100) === 1) {
            DB::table('trip_locations')
                ->where('recorded_at', '<', Carbon::now()->subDays(5))
                ->limit(1000)
                ->delete();
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

        // 🔥 AUTO FINALIZAÇÃO
        if ($trip->auto_finish_pending && $trip->auto_finish_at <= now()) {
            $trip->update([
                'status' => 'finished',
                'auto_finish_pending' => false,
                'auto_finish_at' => null
            ]);
        }

        if (!$trip->latestLocation) {
            return response()->json([
                'success' => true,
                'data' => null
            ]);
        }

        $lat = $trip->latestLocation->latitude;
        $lng = $trip->latestLocation->longitude;

        $stops = RouteStop::where('school_route_id', $trip->school_route_id)
            ->orderBy('stop_order')
            ->get()
            ->values();

        $nextStop = $stops->firstWhere(
            'stop_order',
            $trip->current_stop_order
        );

        $distance = null;

        if ($nextStop) {
            $distance = GeoHelper::distanceMeters(
                $lat,
                $lng,
                $nextStop->latitude,
                $nextStop->longitude
            );
        }

        $eta = $nextStop
            ? $this->calculateETA($trip, $stops, $nextStop)
            : null;

        return response()->json([
            'success' => true,
            'data' => [
                'lat' => $lat,
                'lng' => $lng,
                'recorded_at' => $trip->latestLocation->recorded_at,
                'distance' => $distance,
                'next_stop' => $nextStop ? [
                    'id' => $nextStop->id,
                    'name' => $nextStop->name,
                    'order' => $nextStop->stop_order,
                ] : null,
                'eta_seconds' => $eta,
                'auto_finish_pending' => $trip->auto_finish_pending,
                'auto_finish_seconds' => $trip->auto_finish_at
                    ? max(0, now()->diffInSeconds($trip->auto_finish_at, false))
                    : null,
            ]
        ]);
    }

    private function updateStopMetrics($trip, $fromStopId, $toStopId, $timeSpent)
    {
        if ($timeSpent < 10 || $timeSpent > 1800) return;

        $period = $this->getPeriod();

        $metric = DB::table('stop_time_metrics')
            ->where('route_id', $trip->school_route_id)
            ->where('from_stop_id', $fromStopId)
            ->where('to_stop_id', $toStopId)
            ->where('period', $period)
            ->first();

        if ($metric) {
            $newAvg = ($metric->avg_time_seconds * 0.7) + ($timeSpent * 0.3);

            DB::table('stop_time_metrics')
                ->where('id', $metric->id)
                ->update([
                    'avg_time_seconds' => (int) $newAvg,
                    'updated_at' => now()
                ]);
        } else {
            DB::table('stop_time_metrics')->insert([
                'route_id' => $trip->school_route_id,
                'from_stop_id' => $fromStopId,
                'to_stop_id' => $toStopId,
                'avg_time_seconds' => $timeSpent,
                'period' => $period,
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }
    }

    private function getPeriod()
    {
        $hour = now()->hour;

        if ($hour >= 6 && $hour < 12) return 'morning';
        if ($hour >= 12 && $hour < 18) return 'afternoon';
        if ($hour >= 18 && $hour < 24) return 'evening';

        return 'night';
    }

    private function calculateETA($trip, $stops, $currentStop)
    {
        $currentIndex = $stops->search(fn($s) => $s->id === $currentStop->id);

        if ($currentIndex === false) return null;

        $eta = 0;
        $period = $this->getPeriod();

        for ($i = $currentIndex; $i < count($stops) - 1; $i++) {

            $from = $stops[$i];
            $to = $stops[$i + 1];

            $metric = DB::table('stop_time_metrics')
                ->where('route_id', $trip->school_route_id)
                ->where('from_stop_id', $from->id)
                ->where('to_stop_id', $to->id)
                ->where('period', $period)
                ->first();

            $eta += $metric ? $metric->avg_time_seconds : 120;
        }

        return $eta;
    }
}
